<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;

/**
 * ReferenceData Controller
 *
 * Public endpoints for reference data (calibers, brands).
 *
 * @property \App\Model\Table\CalibersTable $Calibers
 * @property \App\Model\Table\BrandsTable $Brands
 */
class ReferenceDataController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Calibers = $this->fetchTable('Calibers');
        $this->Brands = $this->fetchTable('Brands');
    }

    /**
     * Return all active calibers grouped by weapon_category.
     */
    public function calibers()
    {
        $this->request->allowMethod(['get']);

        $calibers = $this->Calibers->find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => 'ASC', 'name' => 'ASC'])
            ->all();

        $grouped = [
            'pistol' => [],
            'rifle' => [],
            'rimfire' => [],
            'shotshell' => [],
        ];

        foreach ($calibers as $caliber) {
            $category = $caliber->weapon_category;
            if (isset($grouped[$category])) {
                $grouped[$category][] = [
                    'id' => $caliber->id,
                    'name' => $caliber->name,
                    'standard' => $caliber->standard,
                ];
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'calibers' => $grouped,
            ]));
    }

    /**
     * Return all active brands.
     */
    public function brands()
    {
        $this->request->allowMethod(['get']);

        $brands = $this->Brands->find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => 'ASC', 'name' => 'ASC'])
            ->all();

        $result = [];
        foreach ($brands as $brand) {
            $result[] = [
                'id' => $brand->id,
                'name' => $brand->name,
                'country' => $brand->country,
            ];
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'brands' => $result,
            ]));
    }
}
