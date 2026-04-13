<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncSessionsFixture extends TestFixture
{
    public string $table = 'sync_sessions';

    /**
     * @var array<array<string, mixed>>
     */
    public array $records = [];
}
