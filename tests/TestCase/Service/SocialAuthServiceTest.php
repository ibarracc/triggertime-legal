<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SocialAuthService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class SocialAuthServiceTest extends TestCase
{
    protected SocialAuthService $service;

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('SocialAuth.google.clientId', 'test-google-client-id');
        Configure::write('SocialAuth.apple.serviceId', 'test-apple-service-id');
        $this->service = new SocialAuthService();
    }

    public function testVerifyGoogleTokenRejectsInvalidToken(): void
    {
        $result = $this->service->verifyIdToken('google', 'invalid.token.here');
        $this->assertNull($result);
    }

    public function testVerifyAppleTokenRejectsInvalidToken(): void
    {
        $result = $this->service->verifyIdToken('apple', 'invalid.token.here');
        $this->assertNull($result);
    }

    public function testVerifyTokenRejectsUnsupportedProvider(): void
    {
        $result = $this->service->verifyIdToken('facebook', 'some.token');
        $this->assertNull($result);
    }
}
