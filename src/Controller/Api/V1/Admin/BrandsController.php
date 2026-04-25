<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;

/**
 * Admin Brands Controller
 *
 * @property \App\Model\Table\BrandsTable $Brands
 */
class BrandsController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Brands = $this->fetchTable('Brands');
    }

    /**
     * List all brands.
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        $brands = $this->Brands->find()
            ->orderBy(['sort_order' => 'ASC', 'name' => 'ASC'])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'brands' => $brands,
            ]));
    }

    /**
     * Add a new brand.
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $brand = $this->Brands->newEntity($this->request->getData());

        if ($this->Brands->save($brand)) {
            return $this->response->withStatus(201)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'brand' => $brand,
                ]));
        }

        return $this->response->withStatus(400)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'errors' => $brand->getErrors(),
            ]));
    }

    /**
     * Edit an existing brand.
     *
     * @param string $id Brand ID.
     */
    public function edit(string $id)
    {
        $this->request->allowMethod(['put', 'patch', 'post']);

        $brand = $this->Brands->get($id);
        $brand = $this->Brands->patchEntity($brand, $this->request->getData());

        if ($this->Brands->save($brand)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'brand' => $brand,
                ]));
        }

        return $this->response->withStatus(400)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'errors' => $brand->getErrors(),
            ]));
    }

    /**
     * Delete a brand.
     *
     * @param string $id Brand ID.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['delete']);

        $brand = $this->Brands->get($id);

        if ($this->Brands->delete($brand)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'message' => 'Brand deleted',
                ]));
        }

        return $this->response->withStatus(400)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => 'Failed to delete brand',
            ]));
    }
}
