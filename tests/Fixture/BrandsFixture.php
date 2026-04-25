<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BrandsFixture
 */
class BrandsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'name' => 'Federal',
                'country' => 'USA',
                'is_active' => true,
                'sort_order' => 0,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => 'Hornady',
                'country' => 'USA',
                'is_active' => true,
                'sort_order' => 1,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => 'Sellier & Bellot',
                'country' => 'Czech Republic',
                'is_active' => true,
                'sort_order' => 2,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => 'Inactive Brand',
                'country' => null,
                'is_active' => false,
                'sort_order' => 99,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
        ];
        parent::init();
    }
}
