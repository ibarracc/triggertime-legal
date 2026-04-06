<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Service\JwtService;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class RemoteConfigControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Instances',
        'app.Versions',
        'app.AppRemoteConfig',
    ];

    /**
     * Generate an admin JWT for authenticated requests.
     */
    private function getAdminToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => 'test-admin-id',
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
    }

    /**
     * Helper to configure an authenticated admin JSON request.
     */
    private function configureAdminRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAdminToken(),
            ],
        ]);
    }

    public function testDuplicateSuccess(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 2,
        ]));
        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('279fd979-5501-4ea2-9137-0160f3770c85', $body['config']['instance_id']);
        $this->assertEquals(2, $body['config']['version_id']);
        // Verify config_data was copied from source
        $configData = $body['config']['config_data'];
        $parsed = is_string($configData) ? json_decode($configData, true) : $configData;
        $this->assertTrue($parsed['feature_enabled']);
        $this->assertEquals(3, $parsed['max_retries']);
    }

    public function testDuplicateSourceNotFound(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/9999/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
        ]));
        $this->assertResponseCode(404);
    }

    public function testDuplicateMissingInstanceId(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([]));
        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('instance_id', $body['message']);
    }

    public function testDuplicateExistingPairReturns422(): void
    {
        $this->configureAdminRequest();
        // The fixture already has instance_id + version_id=1 pair
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 1,
        ]));
        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertStringContainsString('already exists', $body['message']);
    }

    public function testDuplicateVersionNotBelongingToInstance(): void
    {
        $this->configureAdminRequest();
        // version_id=2 belongs to the fixture instance, but use a non-existent instance
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '00000000-0000-0000-0000-000000000000',
            'version_id' => 2,
        ]));
        $this->assertResponseCode(422);
    }

    public function testDuplicateToSameInstanceDifferentVersion(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 2,
        ]));
        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertEquals(2, $body['config']['version_id']);
    }

    public function testDuplicateToGlobalVersion(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => null,
        ]));
        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertNull($body['config']['version_id']);
    }
}
