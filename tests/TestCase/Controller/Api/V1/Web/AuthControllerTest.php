<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Web;

use App\Service\EmailVerificationService;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\Utility\Text;

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

    public function testRegisterAcceptsMarketingOptin(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'newuser@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'marketing_optin' => true,
        ]));
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertTrue($body['user']['marketing_optin']);
    }

    public function testRegisterDefaultsMarketingOptinToFalse(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'newuser2@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]));
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertFalse($body['user']['marketing_optin']);
    }

    public function testDeleteAccountRequiresEmail(): void
    {
        // First register a user to get a token
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'delete-test@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Delete',
            'last_name' => 'Test',
        ]));
        $body = json_decode((string)$this->_response->getBody(), true);
        $token = $body['token'];

        // Try to delete without email
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'input' => json_encode([]),
        ]);
        $this->delete('/api/v1/web/me');
        $this->assertResponseCode(400);
    }

    public function testDeleteAccountRejectsWrongEmail(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'delete-test2@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Delete',
            'last_name' => 'Test',
        ]));
        $body = json_decode((string)$this->_response->getBody(), true);
        $token = $body['token'];

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'input' => json_encode(['email' => 'wrong@example.com']),
        ]);
        $this->delete('/api/v1/web/me');
        $this->assertResponseCode(400);
    }

    public function testDeleteAccountSucceeds(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'delete-test3@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Delete',
            'last_name' => 'Test',
        ]));
        $body = json_decode((string)$this->_response->getBody(), true);
        $token = $body['token'];

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'input' => json_encode(['email' => 'delete-test3@example.com']),
        ]);
        $this->delete('/api/v1/web/me');
        $this->assertResponseOk();
        $deleteBody = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($deleteBody['success']);
    }

    public function testVerifyEmailRejectsMissingParams(): void
    {
        $this->get('/api/v1/web/auth/verify-email');
        $this->assertResponseCode(400);
    }

    public function testVerifyEmailRejectsInvalidSignature(): void
    {
        $this->get('/api/v1/web/auth/verify-email?uid=fake-id&exp=9999999999&sig=invalidsig');
        $this->assertResponseCode(400);
    }

    public function testVerifyEmailAcceptsValidSignature(): void
    {
        Security::setSalt('test-salt-for-verification-tests-must-be-long-enough');

        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'verify-test@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Verify',
            'last_name' => 'Test',
        ]));
        $body = json_decode((string)$this->_response->getBody(), true);
        $userId = $body['user']['id'];
        $this->assertNull($body['user']['email_verified_at']);

        $service = new EmailVerificationService();
        $url = $service->generateSignedUrl($userId);
        $parsed = parse_url($url);
        parse_str($parsed['query'], $query);

        $this->get('/api/v1/web/auth/verify-email?' . http_build_query($query));
        $this->assertResponseOk();
        $verifyBody = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($verifyBody['success']);
    }

    public function testResendVerificationRequiresAuth(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/resend-verification');
        $this->assertResponseCode(401);
    }

    public function testResendVerificationSucceeds(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'resend-test@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Resend',
            'last_name' => 'Test',
        ]));
        $body = json_decode((string)$this->_response->getBody(), true);
        $token = $body['token'];

        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        $this->post('/api/v1/web/auth/resend-verification');
        $this->assertResponseOk();
        $resendBody = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($resendBody['success']);
    }

    public function testRegisterWithUpgradeTokenSavesPendingToken(): void
    {
        // Create a valid upgrade token directly in DB
        $tokensTable = $this->getTableLocator()->get('UpgradeTokens');
        $token = $tokensTable->newEntity([
            'id' => Text::uuid(),
            'token_string' => 'valid-upgrade-token-123',
            'device_uuid' => 'device-uuid-abc',
            'type' => 'upgrade',
            'is_used' => false,
            'expires_at' => DateTime::now()->addDays(7),
        ]);
        $tokensTable->saveOrFail($token);

        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'upgrade-token-user@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Upgrade',
            'last_name' => 'User',
            'upgrade_token' => 'valid-upgrade-token-123',
        ]));
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);

        // Verify the pending_upgrade_token was saved on the user
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get($body['user']['id']);
        $this->assertSame('valid-upgrade-token-123', $user->pending_upgrade_token);
    }

    public function testRegisterReturnsUnverifiedUser(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/web/auth/register', json_encode([
            'email' => 'unverified-test@example.com',
            'password' => 'securepassword123',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]));
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertNull($body['user']['email_verified_at']);
    }
}
