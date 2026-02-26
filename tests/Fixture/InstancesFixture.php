<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InstancesFixture
 */
class InstancesFixture extends TestFixture
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
                'id' => '279fd979-5501-4ea2-9137-0160f3770c85',
                'name' => 'Lorem ipsum dolor sit amet',
                'club_admin_id' => 'c3792a3c-af61-479e-aaa3-16e763aacbf8',
                'is_active' => 1,
                'created' => 1771994278,
                'modified' => 1771994278,
            ],
        ];
        parent::init();
    }
}
