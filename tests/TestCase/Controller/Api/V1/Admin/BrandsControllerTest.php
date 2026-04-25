<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Service\JwtService;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BrandsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Brands',
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
     * Generate a non-admin JWT.
     */
    private function getUserToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => 'test-user-id',
            'email' => 'user@test.com',
            'role' => 'user',
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

    public function testIndexSuccess(): void
    {
        $this->configureAdminRequest();
        $this->get('/api/v1/admin/brands');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertCount(4, $body['brands']); // includes inactive
    }

    public function testIndexUnauthorized(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->get('/api/v1/admin/brands');
        $this->assertResponseCode(401);
    }

    public function testIndexForbiddenForRegularUser(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getUserToken(),
            ],
        ]);
        $this->get('/api/v1/admin/brands');
        $this->assertResponseCode(403);
    }

    public function testAddSuccess(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/brands', json_encode([
            'name' => 'Winchester Test',
            'country' => 'USA',
            'is_active' => true,
            'sort_order' => 10,
        ]));
        $this->assertResponseCode(201);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Winchester Test', $body['brand']['name']);
        $this->assertEquals('USA', $body['brand']['country']);
    }

    public function testAddValidationErrorEmptyName(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/brands', json_encode([
            'name' => '',
            'country' => 'USA',
        ]));
        $this->assertResponseCode(400);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
    }

    public function testAddDuplicateNameError(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/brands', json_encode([
            'name' => 'Federal',
            'country' => 'USA',
        ]));
        $this->assertResponseCode(400);
    }

    public function testAddWithNullCountry(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/brands', json_encode([
            'name' => 'Mystery Brand',
            'country' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]));
        $this->assertResponseCode(201);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertNull($body['brand']['country']);
    }

    public function testEditSuccess(): void
    {
        $this->configureAdminRequest();
        // Get first brand ID
        $this->get('/api/v1/admin/brands');
        $body = json_decode((string)$this->_response->getBody(), true);
        $brandId = $body['brands'][0]['id'];

        $this->configureAdminRequest();
        $this->put("/api/v1/admin/brands/{$brandId}", json_encode([
            'name' => 'Federal Premium',
            'country' => 'United States',
        ]));
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Federal Premium', $body['brand']['name']);
        $this->assertEquals('United States', $body['brand']['country']);
    }

    public function testEditNotFound(): void
    {
        $this->configureAdminRequest();
        $this->put('/api/v1/admin/brands/99999', json_encode([
            'name' => 'Does Not Exist',
        ]));
        $this->assertResponseCode(404);
    }

    public function testDeleteSuccess(): void
    {
        $this->configureAdminRequest();
        // Get first brand ID
        $this->get('/api/v1/admin/brands');
        $body = json_decode((string)$this->_response->getBody(), true);
        $brandId = $body['brands'][0]['id'];

        $this->configureAdminRequest();
        $this->delete("/api/v1/admin/brands/{$brandId}");
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
    }

    public function testDeleteNotFound(): void
    {
        $this->configureAdminRequest();
        $this->delete('/api/v1/admin/brands/99999');
        $this->assertResponseCode(404);
    }
}
