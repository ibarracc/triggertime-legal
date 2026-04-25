<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ReferenceDataControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Calibers',
        'app.Brands',
    ];

    /**
     * Helper to configure a JSON request without auth.
     */
    private function configurePublicRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function testCalibersReturnsGroupedActiveOnly(): void
    {
        $this->configurePublicRequest();
        $this->get('/api/v1/reference/calibers');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('calibers', $body);
        $this->assertArrayHasKey('pistol', $body['calibers']);
        $this->assertArrayHasKey('rifle', $body['calibers']);
        $this->assertArrayHasKey('rimfire', $body['calibers']);
        $this->assertArrayHasKey('shotshell', $body['calibers']);

        // Should only contain active calibers (not the inactive one)
        $allCalibers = array_merge(
            $body['calibers']['pistol'],
            $body['calibers']['rifle'],
            $body['calibers']['rimfire'],
            $body['calibers']['shotshell'],
        );
        $this->assertCount(4, $allCalibers);

        // Check that inactive caliber is excluded
        $names = array_column($allCalibers, 'name');
        $this->assertNotContains('Inactive Caliber', $names);

        // Check structure of each caliber entry
        $firstPistol = $body['calibers']['pistol'][0];
        $this->assertArrayHasKey('id', $firstPistol);
        $this->assertArrayHasKey('name', $firstPistol);
        $this->assertArrayHasKey('standard', $firstPistol);
    }

    public function testCalibersMethodNotAllowed(): void
    {
        $this->configurePublicRequest();
        $this->post('/api/v1/reference/calibers');
        $this->assertResponseCode(405);
    }

    public function testBrandsReturnsActiveOnly(): void
    {
        $this->configurePublicRequest();
        $this->get('/api/v1/reference/brands');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('brands', $body);
        $this->assertCount(3, $body['brands']);

        // Check inactive brand is excluded
        $names = array_column($body['brands'], 'name');
        $this->assertNotContains('Inactive Brand', $names);

        // Check structure
        $firstBrand = $body['brands'][0];
        $this->assertArrayHasKey('id', $firstBrand);
        $this->assertArrayHasKey('name', $firstBrand);
        $this->assertArrayHasKey('country', $firstBrand);
    }

    public function testBrandsMethodNotAllowed(): void
    {
        $this->configurePublicRequest();
        $this->post('/api/v1/reference/brands');
        $this->assertResponseCode(405);
    }
}
