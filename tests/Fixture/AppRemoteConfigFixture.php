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
        $this->records = [];
        parent::init();
    }
}
