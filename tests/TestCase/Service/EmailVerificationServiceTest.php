<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\EmailVerificationService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    private EmailVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Security.salt', 'test-salt-for-unit-tests-must-be-long-enough');
        Configure::write('App.fullBaseUrl', 'https://triggertime.es');
        $this->service = new EmailVerificationService();
    }

    public function testGenerateSignedUrlContainsRequiredParams(): void
    {
        $url = $this->service->generateSignedUrl('user-uuid-123');

        $parsed = parse_url($url);
        parse_str($parsed['query'], $query);

        $this->assertSame('user-uuid-123', $query['uid']);
        $this->assertArrayHasKey('exp', $query);
        $this->assertArrayHasKey('sig', $query);
    }

    public function testVerifySignedUrlAcceptsValidUrl(): void
    {
        $url = $this->service->generateSignedUrl('user-uuid-123');
        $parsed = parse_url($url);
        parse_str($parsed['query'], $query);

        $result = $this->service->verifySignedUrl($query['uid'], $query['exp'], $query['sig']);

        $this->assertSame('user-uuid-123', $result);
    }

    public function testVerifySignedUrlRejectsExpiredUrl(): void
    {
        $expiry = (string)(time() - 1);
        $sig = hash_hmac('sha256', 'user-uuid-123:' . $expiry, Configure::read('Security.salt'));

        $result = $this->service->verifySignedUrl('user-uuid-123', $expiry, $sig);

        $this->assertNull($result);
    }

    public function testVerifySignedUrlRejectsTamperedSignature(): void
    {
        $url = $this->service->generateSignedUrl('user-uuid-123');
        $parsed = parse_url($url);
        parse_str($parsed['query'], $query);

        $result = $this->service->verifySignedUrl($query['uid'], $query['exp'], 'tampered-signature');

        $this->assertNull($result);
    }

    public function testVerifySignedUrlRejectsTamperedUserId(): void
    {
        $url = $this->service->generateSignedUrl('user-uuid-123');
        $parsed = parse_url($url);
        parse_str($parsed['query'], $query);

        $result = $this->service->verifySignedUrl('different-user-id', $query['exp'], $query['sig']);

        $this->assertNull($result);
    }

    public function testDefaultExpiryIs7Days(): void
    {
        $url = $this->service->generateSignedUrl('user-uuid-123');
        $parsed = parse_url($url);
        parse_str($parsed['query'], $query);

        $expiry = (int)$query['exp'];
        $expectedMin = time() + (7 * 24 * 3600) - 5;
        $expectedMax = time() + (7 * 24 * 3600) + 5;

        $this->assertGreaterThanOrEqual($expectedMin, $expiry);
        $this->assertLessThanOrEqual($expectedMax, $expiry);
    }
}
