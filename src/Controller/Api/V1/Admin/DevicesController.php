<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;

/**
 * @property \App\Model\Table\DevicesTable $Devices
 */
class DevicesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Devices = $this->fetchTable('Devices');
    }

    private function ensureAdmin()
    {
        $payload = $this->request->getAttribute('jwt_payload');
        if (!isset($payload['role']) || $payload['role'] !== 'admin') {
            throw new ForbiddenException('Strictly Super Admin Only');
        }
    }

    public function index()
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['get']);

        $devices = $this->Devices->find()
            ->contain(['Users', 'Instances'])
            ->all();

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'devices' => $devices
        ]));
    }

    public function add()
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['post']);
        $data = $this->request->getData();

        $device = $this->Devices->newEntity($data);
        if ($this->Devices->save($device)) {
            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'device' => $device
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'errors' => $device->getErrors()
        ]));
    }

    public function edit(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['put', 'patch', 'post']);

        $device = $this->Devices->get($id);
        $device = $this->Devices->patchEntity($device, $this->request->getData());

        if ($this->Devices->save($device)) {
            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'device' => $device
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'errors' => $device->getErrors()
        ]));
    }

    public function view(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['get']);

        $device = $this->Devices->get($id, [
            'contain' => ['Users', 'Instances', 'Subscriptions']
        ]);

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'device' => $device
        ]));
    }

    public function delete(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['delete']);
        $device = $this->Devices->get($id);

        if ($this->Devices->delete($device)) {
            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'message' => 'Device soft deleted'
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'message' => 'Failed to delete device'
        ]));
    }
}
