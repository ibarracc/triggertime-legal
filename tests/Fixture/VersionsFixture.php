<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * VersionsFixture
 */
class VersionsFixture extends TestFixture
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
                'id' => 1,
                'version' => '1.0.0',
                'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
                'created' => 1771994278,
                'modified' => 1771994278,
            ],
            [
                'id' => 2,
                'version' => '2.0.0',
                'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
                'created' => 1771994278,
                'modified' => 1771994278,
            ],
        ];
        parent::init();
    }
}
