<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CalibersFixture
 */
class CalibersFixture extends TestFixture
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
                'name' => '9mm Luger',
                'weapon_category' => 'pistol',
                'standard' => 'saami',
                'is_active' => true,
                'sort_order' => 0,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => '223 Remington',
                'weapon_category' => 'rifle',
                'standard' => 'saami',
                'is_active' => true,
                'sort_order' => 0,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => '22 Long Rifle',
                'weapon_category' => 'rimfire',
                'standard' => 'cip',
                'is_active' => true,
                'sort_order' => 0,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => '12 Gauge 2 3/4" Smooth bore',
                'weapon_category' => 'shotshell',
                'standard' => 'saami',
                'is_active' => true,
                'sort_order' => 0,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'name' => 'Inactive Caliber',
                'weapon_category' => 'pistol',
                'standard' => 'cip',
                'is_active' => false,
                'sort_order' => 99,
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
        ];
        parent::init();
    }
}
