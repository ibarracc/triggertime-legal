<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;

/**
 * @property \App\Model\Table\VersionsTable $Versions
 */
class VersionsController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Versions = $this->fetchTable('Versions');
    }

    /**
     * List all versions with associated instances.
     */
    public function index()
    {
        $this->request->allowMethod(['get']);
        $versions = $this->Versions->find()
            ->contain(['Instances'])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true, 'versions' => $versions]));
    }

    /**
     * Create a new version entry.
     */
    public function add()
    {
        $this->request->allowMethod(['post']);
        $version = $this->Versions->newEmptyEntity();
        $version = $this->Versions->patchEntity($version, $this->request->getData());

        if ($this->Versions->save($version)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true, 'version' => $version]));
        }

        throw new BadRequestException('Could not save version');
    }

    /**
     * Update an existing version entry.
     *
     * @param string $id Version record ID.
     */
    public function edit(string $id)
    {
        $this->request->allowMethod(['put', 'post', 'patch']);
        $version = $this->Versions->get($id);

        $version = $this->Versions->patchEntity($version, $this->request->getData());
        if ($this->Versions->save($version)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true, 'version' => $version]));
        }

        throw new BadRequestException('Could not update version');
    }

    /**
     * Delete a version entry.
     *
     * @param string $id Version record ID.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['delete']);
        $version = $this->Versions->get($id);

        if ($this->Versions->delete($version)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true]));
        }

        throw new BadRequestException('Could not delete version');
    }
}
