<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Text;
use Cake\Core\Configure;

use App\Service\JwtService;

/**
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Users = $this->fetchTable('Users');
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

        $users = $this->Users->find()
            ->contain(['Devices', 'Subscriptions', 'ActivationLicenses'])
            ->all();

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'users' => $users
        ]));
    }

    public function add()
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['post']);
        $data = $this->request->getData();

        $data['id'] = Text::uuid();
        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $user = $this->Users->newEntity($data);
        if ($this->Users->save($user)) {

            // If they are a club admin and provided an instance_id, link it
            if ($user->role === 'club_admin' && !empty($data['instance_id'])) {
                $Instances = $this->fetchTable('Instances');
                try {
                    $instance = $Instances->get($data['instance_id']);
                    $instance->club_admin_id = $user->id;
                    $Instances->save($instance);
                } catch (\Exception $e) {
                    // Ignore invalid instance IDs gracefully logging skipped
                }
            }
            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'user' => $user
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'errors' => $user->getErrors()
        ]));
    }

    public function edit(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['put', 'patch', 'post']);

        $user = $this->Users->get($id);
        $data = $this->request->getData();

        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $user = $this->Users->patchEntity($user, $data);

        if ($this->Users->save($user)) {

            // Update the instance linking if role is club_admin and an instance_id is provided
            if ($user->role === 'club_admin' && isset($data['instance_id'])) {
                $Instances = $this->fetchTable('Instances');

                // Unlink old instances mapped to this user just in case they switched instances
                $Instances->updateAll(
                    ['club_admin_id' => null],
                    ['club_admin_id' => $user->id]
                );

                if (!empty($data['instance_id'])) {
                    try {
                        $instance = $Instances->get($data['instance_id']);
                        $instance->club_admin_id = $user->id;
                        $Instances->save($instance);
                    } catch (\Exception $e) {
                    }
                }
            }

            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'user' => $user
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'errors' => $user->getErrors()
        ]));
    }

    public function view(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['get']);

        $user = $this->Users->get($id, [
            'contain' => ['Devices', 'Subscriptions', 'ActivationLicenses']
        ]);

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'user' => $user
        ]));
    }

    public function delete(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['delete']);
        $user = $this->Users->get($id);

        if ($this->Users->delete($user)) {
            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'message' => 'User soft deleted'
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'message' => 'Failed to delete user'
        ]));
    }

    public function impersonate(string $id)
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['post']);

        $targetUser = $this->Users->get($id);

        $jwt = new JwtService();
        $payload = [
            'sub' => $targetUser->id,
            'email' => $targetUser->email,
            'role' => $targetUser->role,
            'impersonated_by' => $this->request->getAttribute('jwt_payload')['sub']
        ];

        $token = $jwt->generateToken($payload, Configure::read('Security.jwtExpiry', 86400));

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'token' => $token,
            'user' => $targetUser
        ]));
    }
}
