<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AppRemoteConfigFixture
 */
class AppRemoteConfigFixture extends TestFixture
{
    /**
     * Table alias - use RemoteConfig since that's the ORM class for app_remote_config
     *
     * @var string
     */
    public string $tableAlias = 'RemoteConfig';

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
                'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
                'version_id' => 1,
                'config_data' => '{"feature_enabled":true,"max_retries":3}',
                'created' => '2026-03-01 00:00:00',
                'modified' => '2026-03-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
