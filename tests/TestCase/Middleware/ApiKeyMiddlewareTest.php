<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ApiKeyMiddlewareTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [];

    private string $apiKey = 'tt_live_a8f3k2m9xQ7bR4cN';
    private string $secret = 'hmac-secret-for-triggertime';

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('ApiKeys', [
            'verify_signature' => true,
            'keys' => [
                $this->apiKey => [
                    'app_instance' => 'com.ibarracc.triggertime',
                    'secret' => $this->secret,
                ],
            ],
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('ApiKeys');
    }

    public function testInvalidSignatureReturns401(): void
    {
        $body = json_encode(['device_uuid' => 'test-uuid', 'hardware_model' => 'iPhone']);
        $timestamp = (string)time();

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => 'invalid-signature-value',
            ],
        ]);
        $this->post('/api/v1/devices/register', $body);
        $this->assertResponseCode(401);
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertStringContainsString('Invalid API Signature', $response['error']['message']);
    }

    public function testValidSignaturePassesMiddleware(): void
    {
        $body = json_encode(['device_uuid' => 'test-uuid-valid', 'hardware_model' => 'iPhone']);
        $timestamp = (string)time();
        $message = $timestamp . '.' . $body;
        $signature = hash_hmac('sha256', $message, $this->secret);

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
            ],
        ]);
        $this->post('/api/v1/devices/register', $body);
        // Should not be rejected by middleware (401).
        // The request may succeed (200) or fail due to DB issues (500) — either
        // proves the request passed signature verification.
        $statusCode = $this->_response->getStatusCode();
        $this->assertNotEquals(401, $statusCode, 'Request should pass HMAC signature verification');
    }

    public function testMissingSignatureHeadersReturns401(): void
    {
        $body = json_encode(['device_uuid' => 'test-uuid', 'hardware_model' => 'iPhone']);

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
            ],
        ]);
        $this->post('/api/v1/devices/register', $body);
        $this->assertResponseCode(401);
    }

    public function testExpiredTimestampReturns401(): void
    {
        $body = json_encode(['device_uuid' => 'test-uuid', 'hardware_model' => 'iPhone']);
        $timestamp = (string)(time() - 600); // 10 minutes ago
        $message = $timestamp . '.' . $body;
        $signature = hash_hmac('sha256', $message, $this->secret);

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
            ],
        ]);
        $this->post('/api/v1/devices/register', $body);
        $this->assertResponseCode(401);
    }
}
