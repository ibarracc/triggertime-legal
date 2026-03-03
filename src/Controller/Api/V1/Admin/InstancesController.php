<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;

/**
 * @property \App\Model\Table\InstancesTable $Instances
 */
class InstancesController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Instances = $this->fetchTable('Instances');
    }

    /**
     * Throw a ForbiddenException if the current user is not a super admin.
     */
    private function ensureAdmin()
    {
        $payload = $this->request->getAttribute('jwt_payload');
        if (!isset($payload['role']) || $payload['role'] !== 'admin') {
            throw new ForbiddenException('Strictly Super Admin Only');
        }
    }

    /**
     * List all instances, filtered by role (club_admin sees only their own).
     */
    public function index()
    {
        $payload = $this->request->getAttribute('jwt_payload');
        $role = $payload['role'] ?? null;
        $userId = $payload['sub'] ?? null;

        $this->request->allowMethod(['get']);

        if ($role === 'club_admin' && $userId) {
            $instances = $this->Instances->find()->contain(['ClubAdmins'])->where(['club_admin_id' => $userId])->all();
        } else {
            $this->ensureAdmin();
            $instances = $this->Instances->find()->contain(['ClubAdmins'])->all();
        }

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'instances' => $instances,
        ]));
    }

    /**
     * Create a new instance.
     */
    public function add()
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['post']);
        $data = $this->request->getData();

        $instance = $this->Instances->newEntity($data);
        if ($this->Instances->save($instance)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
                'instance' => $instance,
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'errors' => $instance->getErrors(),
        ]));
    }

    /**
     * Update an existing instance.
     */
    public function edit(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['put', 'patch', 'post']);
        $instance = $this->Instances->get($id);

        $instance = $this->Instances->patchEntity($instance, $this->request->getData());
        if ($this->Instances->save($instance)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
                'instance' => $instance,
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'errors' => $instance->getErrors(),
        ]));
    }

    /**
     * Display a single instance's details.
     */
    public function view(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['get']);
        $instance = $this->Instances->get($id);

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'instance' => $instance,
        ]));
    }

    /**
     * Delete an instance.
     */
    public function delete(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['delete']);
        $instance = $this->Instances->get($id);

        if ($this->Instances->delete($instance)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'message' => 'Failed to delete instance',
        ]));
    }
}
