<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;

class RemoteConfigController extends AppController
{
    public function index()
    {
        $this->request->allowMethod(['get']);
        $configs = $this->fetchTable('RemoteConfig')->find()
            ->contain(['Instances', 'Versions'])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'configs' => $configs]));
    }

    public function view($id)
    {
        $this->request->allowMethod(['get']);
        $config = $this->fetchTable('RemoteConfig')->get($id, [
            'contain' => ['Instances', 'Versions']
        ]);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'config' => $config]));
    }

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
                ->withStringBody(json_encode(['success' => true, 'config' => $config]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody(json_encode(['success' => false, 'errors' => $config->getErrors()]));
    }

    public function edit($id)
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
                ->withStringBody(json_encode(['success' => true, 'config' => $config]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody(json_encode(['success' => false, 'errors' => $config->getErrors()]));
    }

    public function delete($id)
    {
        $this->request->allowMethod(['delete']);
        $table = $this->fetchTable('RemoteConfig');
        $config = $table->get($id);

        if ($table->delete($config)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true]));
        }

        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody(json_encode(['success' => false, 'message' => 'Could not delete config']));
    }
}
