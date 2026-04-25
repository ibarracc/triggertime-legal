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
                'version' => '1.0.0',
                'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
            [
                'version' => '2.0.0',
                'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
                'created' => '2026-02-25 12:00:00',
                'modified' => '2026-02-25 12:00:00',
            ],
        ];
        parent::init();
    }
}
