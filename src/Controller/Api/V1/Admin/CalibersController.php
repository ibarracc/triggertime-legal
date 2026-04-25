<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;

/**
 * Admin Calibers Controller
 *
 * @property \App\Model\Table\CalibersTable $Calibers
 */
class CalibersController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Calibers = $this->fetchTable('Calibers');
    }

    /**
     * List all calibers.
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        $calibers = $this->Calibers->find()
            ->orderBy(['weapon_category' => 'ASC', 'sort_order' => 'ASC', 'name' => 'ASC'])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'calibers' => $calibers,
            ]));
    }

    /**
     * Add a new caliber.
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $caliber = $this->Calibers->newEntity($this->request->getData());

        if ($this->Calibers->save($caliber)) {
            return $this->response->withStatus(201)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'caliber' => $caliber,
                ]));
        }

        return $this->response->withStatus(400)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'errors' => $caliber->getErrors(),
            ]));
    }

    /**
     * Edit an existing caliber.
     *
     * @param string $id Caliber ID.
     */
    public function edit(string $id)
    {
        $this->request->allowMethod(['put', 'patch', 'post']);

        $caliber = $this->Calibers->get($id);
        $caliber = $this->Calibers->patchEntity($caliber, $this->request->getData());

        if ($this->Calibers->save($caliber)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'caliber' => $caliber,
                ]));
        }

        return $this->response->withStatus(400)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'errors' => $caliber->getErrors(),
            ]));
    }

    /**
     * Delete a caliber.
     *
     * @param string $id Caliber ID.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['delete']);

        $caliber = $this->Calibers->get($id);

        if ($this->Calibers->delete($caliber)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'message' => 'Caliber deleted',
                ]));
        }

        return $this->response->withStatus(400)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => 'Failed to delete caliber',
            ]));
    }
}
