<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;

class RemoteConfigController extends AppController
{
    /**
     * List all remote config entries.
     */
    public function index()
    {
        $this->request->allowMethod(['get']);
        $configs = $this->fetchTable('RemoteConfig')->find()
            ->contain(['Instances', 'Versions'])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true, 'configs' => $configs]));
    }

    /**
     * Display a single remote config entry.
     *
     * @param string $id Config record ID.
     */
    public function view(string $id)
    {
        $this->request->allowMethod(['get']);
        $config = $this->fetchTable('RemoteConfig')->get($id, [
            'contain' => ['Instances', 'Versions'],
        ]);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true, 'config' => $config]));
    }

    /**
     * Create a new remote config entry.
     */
    public function add()
    {
        $this->request->allowMethod(['post']);
        $table = $this->fetchTable('RemoteConfig');
        $config = $table->newEmptyEntity();

        $data = $this->request->getData();

        // Ensure app_instance is populated correctly for legacy reasons
        if (!empty($data['instance_id'])) {
            $instance = $this->fetchTable('Instances')->get($data['instance_id']);
            $data['app_instance'] = $instance->name;
        } else {
            $data['app_instance'] = '*'; // Global fallback
        }

        // Handle JSON config_data
        if (isset($data['config_data']) && is_array($data['config_data'])) {
            $data['config_data'] = json_encode($data['config_data']);
        }

        $config = $table->patchEntity($config, $data);

        if ($table->save($config)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true, 'config' => $config]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode(['success' => false, 'errors' => $config->getErrors()]));
    }

    /**
     * Update an existing remote config entry.
     *
     * @param string $id Config record ID.
     */
    public function edit(string $id)
    {
        $this->request->allowMethod(['put']);
        $table = $this->fetchTable('RemoteConfig');
        $config = $table->get($id);

        $data = $this->request->getData();

        // Handle JSON config_data
        if (isset($data['config_data']) && is_array($data['config_data'])) {
            $data['config_data'] = json_encode($data['config_data']);
        }

        $config = $table->patchEntity($config, $data);

        if ($table->save($config)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true, 'config' => $config]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode(['success' => false, 'errors' => $config->getErrors()]));
    }

    /**
     * Delete a remote config entry.
     *
     * @param string $id Config record ID.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['delete']);
        $table = $this->fetchTable('RemoteConfig');
        $config = $table->get($id);

        if ($table->delete($config)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode(['success' => false, 'message' => 'Could not delete config']));
    }

    /**
     * Duplicate a remote config to a new instance/version combination.
     *
     * @param string $id Source config record ID.
     */
    public function duplicate(string $id)
    {
        $this->request->allowMethod(['post']);
        $table = $this->fetchTable('RemoteConfig');

        // Load source config (throws RecordNotFoundException → 404)
        $source = $table->get($id);

        $data = $this->request->getData();

        // Validate instance_id is present
        if (empty($data['instance_id'])) {
            return $this->response->withType('application/json')->withStatus(422)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'instance_id is required',
                ]));
        }

        // Validate instance exists
        $instancesTable = $this->fetchTable('Instances');
        $instance = $instancesTable->find()->where(['id' => $data['instance_id']])->first();
        if (!$instance) {
            return $this->response->withType('application/json')->withStatus(422)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Instance not found',
                ]));
        }

        // Validate version belongs to selected instance (if provided)
        $versionId = $data['version_id'] ?? null;
        if ($versionId !== null) {
            $versionsTable = $this->fetchTable('Versions');
            $version = $versionsTable->find()
                ->where(['id' => $versionId, 'instance_id' => $data['instance_id']])
                ->first();
            if (!$version) {
                return $this->response->withType('application/json')->withStatus(422)
                    ->withStringBody((string)json_encode([
                        'success' => false,
                        'message' => 'Version does not belong to the selected instance',
                    ]));
            }
        }

        // Check uniqueness (instance_id + version_id pair)
        $existingConditions = [
            'instance_id' => $data['instance_id'],
            'version_id IS' => $versionId,
        ];
        $existing = $table->find()->where($existingConditions)->first();
        if ($existing) {
            return $this->response->withType('application/json')->withStatus(422)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'A config already exists for this instance/version combination',
                ]));
        }

        // Create the duplicate
        $newConfig = $table->newEntity([
            'instance_id' => $data['instance_id'],
            'version_id' => $versionId,
            'config_data' => $source->config_data,
        ]);

        if ($table->save($newConfig)) {
            $newConfig = $table->get($newConfig->id, contain: ['Instances', 'Versions']);

            return $this->response->withType('application/json')->withStatus(201)
                ->withStringBody((string)json_encode(['success' => true, 'config' => $newConfig]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode(['success' => false, 'errors' => $newConfig->getErrors()]));
    }
}
