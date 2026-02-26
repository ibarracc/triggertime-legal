<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;

/**
 * @property \App\Model\Table\VersionsTable $Versions
 */
class VersionsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Versions = $this->fetchTable('Versions');
    }

    public function index()
    {
        $this->request->allowMethod(['get']);
        $versions = $this->Versions->find()
            ->contain(['Instances'])
            ->all();
        return $this->response->withType('application/json')->withStringBody(json_encode(['success' => true, 'versions' => $versions]));
    }

    public function add()
    {
        $this->request->allowMethod(['post']);
        $version = $this->Versions->newEmptyEntity();
        $version = $this->Versions->patchEntity($version, $this->request->getData());

        if ($this->Versions->save($version)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'version' => $version]));
        }

        throw new \Cake\Http\Exception\BadRequestException('Could not save version');
    }

    public function edit($id)
    {
        $this->request->allowMethod(['put', 'post', 'patch']);
        $version = $this->Versions->get($id);

        $version = $this->Versions->patchEntity($version, $this->request->getData());
        if ($this->Versions->save($version)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'version' => $version]));
        }

        throw new \Cake\Http\Exception\BadRequestException('Could not update version');
    }

    public function delete($id)
    {
        $this->request->allowMethod(['delete']);
        $version = $this->Versions->get($id);

        if ($this->Versions->delete($version)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true]));
        }

        throw new \Cake\Http\Exception\BadRequestException('Could not delete version');
    }
}
