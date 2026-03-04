<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Web;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AuthControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [];

    public function testSocialLoginRejectsMissingProvider(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/social-login', json_encode([
            'id_token' => 'some-token',
        ]));
        $this->assertResponseCode(400);
    }

    public function testSocialLoginRejectsMissingIdToken(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/social-login', json_encode([
            'provider' => 'google',
        ]));
        $this->assertResponseCode(400);
    }

    public function testSocialLoginRejectsInvalidProvider(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/social-login', json_encode([
            'provider' => 'facebook',
            'id_token' => 'some-token',
        ]));
        $this->assertResponseCode(401);
    }

    public function testSocialLoginRejectsInvalidToken(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/social-login', json_encode([
            'provider' => 'google',
            'id_token' => 'invalid.jwt.token',
        ]));
        $this->assertResponseCode(401);
    }
}
