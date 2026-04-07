<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Web;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SessionsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [];

    /**
     * Test that GET /api/v1/web/sessions requires authentication.
     */
    public function testIndexRequiresAuth(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->get('/api/v1/web/sessions');
        $this->assertResponseCode(401);
    }

    /**
     * Test that GET /api/v1/web/sessions/{uuid} requires authentication.
     */
    public function testViewRequiresAuth(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->get('/api/v1/web/sessions/00000000-0000-0000-0000-000000000000');
        $this->assertResponseCode(401);
    }
}
