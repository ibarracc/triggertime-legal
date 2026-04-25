<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Service\JwtService;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class CalibersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Calibers',
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
        $this->get('/api/v1/admin/calibers');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertCount(5, $body['calibers']); // includes inactive
    }

    public function testIndexUnauthorized(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->get('/api/v1/admin/calibers');
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
        $this->get('/api/v1/admin/calibers');
        $this->assertResponseCode(403);
    }

    public function testAddSuccess(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/calibers', json_encode([
            'name' => '.45 ACP Test',
            'weapon_category' => 'pistol',
            'standard' => 'saami',
            'is_active' => true,
            'sort_order' => 10,
        ]));
        $this->assertResponseCode(201);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('.45 ACP Test', $body['caliber']['name']);
        $this->assertEquals('pistol', $body['caliber']['weapon_category']);
    }

    public function testAddValidationError(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/calibers', json_encode([
            'name' => '',
            'weapon_category' => 'invalid',
            'standard' => 'saami',
        ]));
        $this->assertResponseCode(400);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
    }

    public function testAddDuplicateError(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/calibers', json_encode([
            'name' => '9mm Luger',
            'weapon_category' => 'pistol',
            'standard' => 'saami',
        ]));
        $this->assertResponseCode(400);
    }

    public function testEditSuccess(): void
    {
        $this->configureAdminRequest();
        // Get first caliber ID
        $this->get('/api/v1/admin/calibers');
        $body = json_decode((string)$this->_response->getBody(), true);
        $caliberId = $body['calibers'][0]['id'];

        $this->configureAdminRequest();
        $this->put("/api/v1/admin/calibers/{$caliberId}", json_encode([
            'name' => '9mm Luger Updated',
            'sort_order' => 5,
        ]));
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('9mm Luger Updated', $body['caliber']['name']);
    }

    public function testEditNotFound(): void
    {
        $this->configureAdminRequest();
        $this->put('/api/v1/admin/calibers/99999', json_encode([
            'name' => 'Does Not Exist',
        ]));
        $this->assertResponseCode(404);
    }

    public function testDeleteSuccess(): void
    {
        $this->configureAdminRequest();
        // Get first caliber ID
        $this->get('/api/v1/admin/calibers');
        $body = json_decode((string)$this->_response->getBody(), true);
        $caliberId = $body['calibers'][0]['id'];

        $this->configureAdminRequest();
        $this->delete("/api/v1/admin/calibers/{$caliberId}");
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
    }

    public function testDeleteNotFound(): void
    {
        $this->configureAdminRequest();
        $this->delete('/api/v1/admin/calibers/99999');
        $this->assertResponseCode(404);
    }
}
