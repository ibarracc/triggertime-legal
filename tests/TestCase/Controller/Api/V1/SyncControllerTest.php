<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SyncControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test that POST /api/v1/sync/push without API key returns 401.
     */
    public function testPushRequiresApiKey(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/sync/push.json', json_encode([
            'device_uuid' => 'test-uuid',
            'records' => [],
        ]));

        $this->assertResponseCode(401);
    }

    /**
     * Test that GET /api/v1/sync/pull without API key returns 401.
     */
    public function testPullRequiresApiKey(): void
    {
        $this->get('/api/v1/sync/pull.json?device_uuid=test-uuid&since=2024-01-01T00:00:00Z');

        $this->assertResponseCode(401);
    }

    /**
     * Test that GET /api/v1/sync/status without API key returns 401.
     */
    public function testStatusRequiresApiKey(): void
    {
        $this->get('/api/v1/sync/status.json?device_uuid=test-uuid');

        $this->assertResponseCode(401);
    }
}
