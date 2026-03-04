# SSO Google & Apple Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Google and Apple SSO sign-in/sign-up to TriggerTime, with account linking to existing email+password users.

**Architecture:** Frontend uses Google Identity Services and Apple JS SDK for popup-based login, which return provider ID tokens. These tokens are sent to a new backend endpoint that verifies them against provider JWKS endpoints using `firebase/php-jwt`, then creates or links users and issues our own JWT.

**Tech Stack:** CakePHP 5.3 (backend), Vue 3 + Pinia (frontend), `firebase/php-jwt` for RS256 verification, Google Identity Services JS, Apple Sign-In JS SDK.

---

### Task 1: Database Migration — Create `social_accounts` table

**Files:**
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSocialAccounts.php`

Use `bin/cake bake migration CreateSocialAccounts` to generate the file, then edit:

**Step 1: Generate and write the migration**

```bash
bin/cake bake migration CreateSocialAccounts
```

Edit the generated file to contain:

```php
<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSocialAccounts extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('social_accounts', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('provider', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('provider_uid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => true])
            ->addIndex(['provider', 'provider_uid'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
```

**Step 2: Run the migration**

```bash
bin/cake migrations migrate
```

Expected: Migration runs successfully, `social_accounts` table created.

**Step 3: Commit**

```bash
git add config/Migrations/*CreateSocialAccounts*
git commit -m "feat: add social_accounts table migration"
```

---

### Task 2: Database Migration — Make `users.password_hash` nullable

**Files:**
- Create: `config/Migrations/YYYYMMDDHHMMSS_MakePasswordHashNullable.php`

**Step 1: Generate and write the migration**

```bash
bin/cake bake migration MakePasswordHashNullable
```

Edit the generated file:

```php
<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MakePasswordHashNullable extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->changeColumn('password_hash', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
        ]);
        $table->update();
    }
}
```

**Step 2: Run the migration**

```bash
bin/cake migrations migrate
```

Expected: Migration runs successfully.

**Step 3: Update UsersTable validation**

Modify `src/Model/Table/UsersTable.php` — change the `password_hash` validation to allow empty for social auth users. Replace:

```php
$validator
    ->requirePresence('password_hash', 'create')
    ->notEmptyString('password_hash');
```

With:

```php
$validator
    ->allowEmptyString('password_hash');
```

**Step 4: Run existing tests to verify no regressions**

```bash
composer test
```

Expected: All existing tests pass.

**Step 5: Commit**

```bash
git add config/Migrations/*MakePasswordHashNullable* src/Model/Table/UsersTable.php
git commit -m "feat: make password_hash nullable for SSO users"
```

---

### Task 3: SocialAccount Model — Entity & Table

**Files:**
- Create: `src/Model/Entity/SocialAccount.php`
- Create: `src/Model/Table/SocialAccountsTable.php`
- Modify: `src/Model/Table/UsersTable.php`
- Modify: `src/Model/Entity/User.php`

**Step 1: Create the SocialAccount entity**

Create `src/Model/Entity/SocialAccount.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SocialAccount extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'provider' => true,
        'provider_uid' => true,
        'created_at' => true,
    ];
}
```

**Step 2: Create the SocialAccountsTable**

Create `src/Model/Table/SocialAccountsTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SocialAccountsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('social_accounts');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('provider', 'create')
            ->notEmptyString('provider')
            ->inList('provider', ['google', 'apple']);

        $validator
            ->requirePresence('provider_uid', 'create')
            ->notEmptyString('provider_uid');

        return $validator;
    }
}
```

**Step 3: Add relationship to UsersTable**

In `src/Model/Table/UsersTable.php`, add after the existing `hasMany('ActivationLicenses')`:

```php
$this->hasMany('SocialAccounts', [
    'foreignKey' => 'user_id',
]);
```

**Step 4: Add `social_accounts` to User entity accessible fields**

In `src/Model/Entity/User.php`, add `'social_accounts' => true` to `$_accessible`.

**Step 5: Run tests**

```bash
composer test
```

Expected: All tests pass.

**Step 6: Commit**

```bash
git add src/Model/Entity/SocialAccount.php src/Model/Table/SocialAccountsTable.php src/Model/Table/UsersTable.php src/Model/Entity/User.php
git commit -m "feat: add SocialAccount model with User relationship"
```

---

### Task 4: SocialAuth Config — Add to `app.php`

**Files:**
- Modify: `config/app.php`

**Step 1: Add SocialAuth config and cache config**

In `config/app.php`, add the `SocialAuth` key after the `Security` block (after line 84):

```php
/*
 * Social authentication provider configuration.
 * Client IDs are used to verify the audience (aud) claim in provider ID tokens.
 */
'SocialAuth' => [
    'google' => [
        'clientId' => env('GOOGLE_CLIENT_ID', ''),
    ],
    'apple' => [
        'serviceId' => env('APPLE_SERVICE_ID', ''),
    ],
],
```

In the `Cache` section, add a `social_auth` config after the `_cake_model_` entry:

```php
'social_auth' => [
    'className' => FileEngine::class,
    'prefix' => 'social_auth_',
    'path' => CACHE . 'social_auth' . DS,
    'serialize' => true,
    'duration' => '+24 hours',
],
```

**Step 2: Commit**

```bash
git add config/app.php
git commit -m "feat: add SocialAuth config and JWKS cache config"
```

---

### Task 5: SocialAuthService — Token Verification

**Files:**
- Create: `src/Service/SocialAuthService.php`

This is the core service. It verifies Google/Apple ID tokens against their JWKS endpoints using `firebase/php-jwt`.

**Step 1: Write the failing test**

Create `tests/TestCase/Service/SocialAuthServiceTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SocialAuthService;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
```

**Step 2: Run the test to verify it fails**

```bash
vendor/bin/phpunit tests/TestCase/Service/SocialAuthServiceTest.php
```

Expected: FAIL — class `SocialAuthService` not found.

**Step 3: Write SocialAuthService**

Create `src/Service/SocialAuthService.php`:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Log\Log;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

class SocialAuthService
{
    private const GOOGLE_JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    private const APPLE_JWKS_URL = 'https://appleid.apple.com/auth/keys';

    private const GOOGLE_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];
    private const APPLE_ISSUER = 'https://appleid.apple.com';

    /**
     * Provider config: JWKS URL, expected issuers, audience config key.
     *
     * @var array<string, array{jwks_url: string, issuers: array<string>, audience_key: string}>
     */
    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            'google' => [
                'jwks_url' => self::GOOGLE_JWKS_URL,
                'issuers' => self::GOOGLE_ISSUERS,
                'audience_key' => 'SocialAuth.google.clientId',
            ],
            'apple' => [
                'jwks_url' => self::APPLE_JWKS_URL,
                'issuers' => [self::APPLE_ISSUER],
                'audience_key' => 'SocialAuth.apple.serviceId',
            ],
        ];
    }

    /**
     * Verify a provider ID token and return extracted claims.
     *
     * @param string $provider Provider name ('google' or 'apple')
     * @param string $idToken The raw JWT ID token from the provider
     * @return array{sub: string, email: string, first_name: string|null, last_name: string|null}|null
     */
    public function verifyIdToken(string $provider, string $idToken): ?array
    {
        if (!isset($this->providers[$provider])) {
            return null;
        }

        $config = $this->providers[$provider];
        $expectedAudience = Configure::read($config['audience_key'], '');

        if (empty($expectedAudience)) {
            Log::error("SocialAuth: Missing audience config for provider '{$provider}'");

            return null;
        }

        try {
            $keys = $this->fetchJwks($provider, $config['jwks_url']);
            if ($keys === null) {
                return null;
            }

            $decoded = JWT::decode($idToken, $keys);
            $payload = (array)$decoded;

            // Verify issuer
            if (!isset($payload['iss']) || !in_array($payload['iss'], $config['issuers'], true)) {
                Log::warning("SocialAuth: Invalid issuer for {$provider}: " . ($payload['iss'] ?? 'missing'));

                return null;
            }

            // Verify audience
            $aud = $payload['aud'] ?? null;
            if ($aud !== $expectedAudience) {
                Log::warning("SocialAuth: Invalid audience for {$provider}: {$aud}");

                return null;
            }

            // Verify email exists
            if (empty($payload['email'])) {
                Log::warning("SocialAuth: No email in token for {$provider}");

                return null;
            }

            // Extract name — Google uses 'given_name'/'family_name', Apple may not include name
            return [
                'sub' => $payload['sub'],
                'email' => $payload['email'],
                'first_name' => $payload['given_name'] ?? null,
                'last_name' => $payload['family_name'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning("SocialAuth: Token verification failed for {$provider}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Fetch and cache JWKS keys for a provider.
     *
     * @param string $provider Provider name (used as cache key prefix)
     * @param string $url JWKS endpoint URL
     * @return \Firebase\JWT\Key[]|null Array of Key objects or null on failure
     */
    private function fetchJwks(string $provider, string $url): ?array
    {
        $cacheKey = "jwks_{$provider}";
        $cached = Cache::read($cacheKey, 'social_auth');

        if ($cached !== null) {
            return JWK::parseKeySet($cached);
        }

        $context = stream_context_create([
            'http' => ['timeout' => 10],
            'ssl' => ['verify_peer' => true],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            Log::error("SocialAuth: Failed to fetch JWKS from {$url}");

            return null;
        }

        $jwks = json_decode($response, true);
        if (!is_array($jwks) || !isset($jwks['keys'])) {
            Log::error("SocialAuth: Invalid JWKS response from {$url}");

            return null;
        }

        Cache::write($cacheKey, $jwks, 'social_auth');

        return JWK::parseKeySet($jwks);
    }
}
```

**Step 4: Run tests to verify they pass**

```bash
vendor/bin/phpunit tests/TestCase/Service/SocialAuthServiceTest.php
```

Expected: All 3 tests pass (invalid tokens are rejected, unsupported provider returns null).

**Step 5: Run code quality checks**

```bash
composer cs-check
```

Fix any issues with `composer cs-fix` if needed.

**Step 6: Commit**

```bash
git add src/Service/SocialAuthService.php tests/TestCase/Service/SocialAuthServiceTest.php
git commit -m "feat: add SocialAuthService for Google/Apple token verification"
```

---

### Task 6: Test Fixture — SocialAccounts fixture

**Files:**
- Create: `tests/Fixture/SocialAccountsFixture.php`

**Step 1: Create the fixture**

Create `tests/Fixture/SocialAccountsFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SocialAccountsFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                'user_id' => 'c3792a3c-af61-479e-aaa3-16e763aacbf8',
                'provider' => 'google',
                'provider_uid' => 'google-uid-123456',
                'created_at' => '2026-03-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
```

This links the existing admin fixture user to a Google account for testing.

**Step 2: Commit**

```bash
git add tests/Fixture/SocialAccountsFixture.php
git commit -m "feat: add SocialAccountsFixture for testing"
```

---

### Task 7: AuthController — `socialLogin` action

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php`
- Modify: `config/routes.php`

**Step 1: Write the failing test**

Create `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Web;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AuthControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.SocialAccounts',
    ];

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
```

**Step 2: Run the test to verify it fails**

```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
```

Expected: FAIL — route not found or action missing.

**Step 3: Add the route**

In `config/routes.php`, add after the `$web->post('/auth/reset-password', ...)` line (around line 75):

```php
$web->post('/auth/social-login', ['controller' => 'Auth', 'action' => 'socialLogin']);
```

**Step 4: Write the `socialLogin` action**

Add to `src/Controller/Api/V1/Web/AuthController.php`. Add `use App\Service\SocialAuthService;` to imports. Add this method:

```php
/**
 * Authenticate or register a user via social provider (Google/Apple).
 */
public function socialLogin()
{
    $this->request->allowMethod(['post']);

    $provider = $this->request->getData('provider');
    $idToken = $this->request->getData('id_token');

    if (!$provider || !$idToken) {
        throw new BadRequestException('Provider and id_token are required');
    }

    $socialAuth = new SocialAuthService();
    $claims = $socialAuth->verifyIdToken($provider, $idToken);

    if (!$claims) {
        throw new UnauthorizedException('Invalid or expired social token');
    }

    $socialAccounts = $this->fetchTable('SocialAccounts');

    // 1. Check if social account already linked
    $existing = $socialAccounts->find()
        ->where([
            'provider' => $provider,
            'provider_uid' => $claims['sub'],
        ])
        ->first();

    if ($existing) {
        // Existing linked user — log them in
        $user = $this->Authentication->find()
            ->where(['id' => $existing->user_id])
            ->contain(['Subscriptions', 'Devices'])
            ->first();
    } else {
        // 2. Check if email matches an existing user
        $user = $this->Authentication->find()
            ->where(['email' => $claims['email']])
            ->contain(['Subscriptions', 'Devices'])
            ->first();

        if ($user) {
            // Link social account to existing user
            $social = $socialAccounts->newEmptyEntity();
            $social->id = Text::uuid();
            $social->user_id = $user->id;
            $social->provider = $provider;
            $social->provider_uid = $claims['sub'];
            $socialAccounts->save($social);
        } else {
            // 3. Create new user
            $firstName = $claims['first_name'] ?? $this->request->getData('first_name');
            $lastName = $claims['last_name'] ?? $this->request->getData('last_name');

            $user = $this->Authentication->newEmptyEntity();
            $user->id = Text::uuid();
            $user->email = $claims['email'];
            $user->role = 'user';
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->language = $this->request->getData('language', 'en');

            if (!$this->Authentication->save($user)) {
                throw new BadRequestException('Could not create user account');
            }

            // Link social account
            $social = $socialAccounts->newEmptyEntity();
            $social->id = Text::uuid();
            $social->user_id = $user->id;
            $social->provider = $provider;
            $social->provider_uid = $claims['sub'];
            $socialAccounts->save($social);

            // Auto-create free subscription
            $subs = $this->fetchTable('Subscriptions');
            $sub = $subs->newEmptyEntity();
            $sub->id = Text::uuid();
            $sub->user_id = $user->id;
            $sub->plan = 'free';
            $sub->status = 'active';
            $sub->max_devices_allowed = Configure::read("Subscriptions.{$sub->plan}.max_devices_allowed");
            $sub->current_period_start = DateTime::now();
            $subs->save($sub);

            // Auto-link B2B licenses
            $licenses = $this->fetchTable('ActivationLicenses');
            $licenses->updateAll(
                ['user_id' => $user->id],
                ['email' => $user->email, 'user_id IS' => null],
            );

            $user->subscriptions = [$sub];
        }
    }

    $jwt = new JwtService();
    $token = $jwt->generateToken([
        'sub' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
    ]);

    return $this->response->withType('application/json')
        ->withStringBody((string)json_encode([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]));
}
```

**Step 5: Run the tests**

```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
```

Expected: All 4 tests pass.

**Step 6: Run code quality checks**

```bash
composer cs-check
```

Fix any issues if needed.

**Step 7: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php config/routes.php tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
git commit -m "feat: add social-login endpoint for Google/Apple SSO"
```

---

### Task 8: Frontend — Auth API & Store

**Files:**
- Modify: `client/src/api/auth.js`
- Modify: `client/src/stores/auth.js`

**Step 1: Add `socialLogin` to auth API module**

In `client/src/api/auth.js`, add this method to `authApi`:

```js
socialLogin(provider, idToken, firstName, lastName) {
    return api.post('/web/auth/social-login', {
        provider,
        id_token: idToken,
        first_name: firstName,
        last_name: lastName,
    });
},
```

**Step 2: Add `socialLogin` action to auth store**

In `client/src/stores/auth.js`, add after the `register` function:

```js
async function socialLogin(provider, idToken, firstName, lastName) {
    try {
        const response = await authApi.socialLogin(provider, idToken, firstName, lastName)
        if (response.success) {
            setAuthData(response.token, response.user, response.user.subscriptions?.[0])
            return { success: true }
        }
    } catch (error) {
        return { success: false, error: error.response?.data?.error?.message || 'Social login failed' }
    }
}
```

Add `socialLogin` to the `return` block of the store.

**Step 3: Verify frontend builds**

```bash
cd client && npm run build
```

Expected: Build succeeds.

**Step 4: Commit**

```bash
git add client/src/api/auth.js client/src/stores/auth.js
git commit -m "feat: add socialLogin to frontend API and auth store"
```

---

### Task 9: Frontend — Social Auth Composable

**Files:**
- Create: `client/src/composables/useSocialAuth.js`

**Step 1: Write the composable**

Create `client/src/composables/useSocialAuth.js`:

```js
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useSocialAuth() {
    const authStore = useAuthStore()
    const isLoading = ref(false)
    const error = ref('')

    let googleInitialized = false

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve()
                return
            }
            const script = document.createElement('script')
            script.src = src
            script.async = true
            script.onload = resolve
            script.onerror = reject
            document.head.appendChild(script)
        })
    }

    async function initGoogle() {
        if (googleInitialized) return
        await loadScript('https://accounts.google.com/gsi/client')
        googleInitialized = true
    }

    async function loginWithGoogle() {
        isLoading.value = true
        error.value = ''

        try {
            await initGoogle()

            const idToken = await new Promise((resolve, reject) => {
                /* global google */
                google.accounts.id.initialize({
                    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
                    callback: (response) => {
                        if (response.credential) {
                            resolve(response.credential)
                        } else {
                            reject(new Error('No credential received'))
                        }
                    },
                })

                google.accounts.id.prompt((notification) => {
                    if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                        // Fallback: use the button-based flow with popup
                        const buttonDiv = document.createElement('div')
                        buttonDiv.style.display = 'none'
                        document.body.appendChild(buttonDiv)
                        google.accounts.id.renderButton(buttonDiv, {
                            type: 'standard',
                            click_listener: () => {},
                        })
                        // Trigger the popup via the rendered button
                        const btn = buttonDiv.querySelector('[role="button"]')
                        if (btn) btn.click()
                        else reject(new Error('Google Sign-In not available'))
                        buttonDiv.remove()
                    }
                })
            })

            const result = await authStore.socialLogin('google', idToken)
            if (!result.success) {
                error.value = result.error
            }
            return result
        } catch (e) {
            error.value = e.message || 'Google sign-in failed'
            return { success: false, error: error.value }
        } finally {
            isLoading.value = false
        }
    }

    async function loginWithApple() {
        isLoading.value = true
        error.value = ''

        try {
            await loadScript('https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js')

            /* global AppleID */
            AppleID.auth.init({
                clientId: import.meta.env.VITE_APPLE_SERVICE_ID,
                scope: 'name email',
                redirectURI: window.location.origin + '/auth/apple-callback',
                usePopup: true,
            })

            const response = await AppleID.auth.signIn()

            const idToken = response.authorization.id_token
            const firstName = response.user?.name?.firstName || null
            const lastName = response.user?.name?.lastName || null

            const result = await authStore.socialLogin('apple', idToken, firstName, lastName)
            if (!result.success) {
                error.value = result.error
            }
            return result
        } catch (e) {
            if (e.error === 'popup_closed_by_user') {
                error.value = ''
                return { success: false, error: 'canceled' }
            }
            error.value = e.message || 'Apple sign-in failed'
            return { success: false, error: error.value }
        } finally {
            isLoading.value = false
        }
    }

    return {
        isLoading,
        error,
        loginWithGoogle,
        loginWithApple,
    }
}
```

**Step 2: Verify build**

```bash
cd client && npm run build
```

Expected: Build succeeds.

**Step 3: Commit**

```bash
git add client/src/composables/useSocialAuth.js
git commit -m "feat: add useSocialAuth composable for Google/Apple popup flows"
```

---

### Task 10: Frontend — i18n translations

**Files:**
- Modify: `client/src/i18n/locales/en.json`
- Modify: all 7 other locale files (`es.json`, `de.json`, `fr.json`, `pt.json`, `eu.json`, `ca.json`, `gl.json`)

**Step 1: Add SSO translation keys to `en.json`**

Add these keys inside the `"auth"` section:

```json
"or_continue_with": "Or continue with",
"sign_in_google": "Sign in with Google",
"sign_in_apple": "Sign in with Apple",
"social_login_failed": "Social login failed. Please try again."
```

**Step 2: Add equivalent keys to all other locale files**

For `es.json`:
```json
"or_continue_with": "O continuar con",
"sign_in_google": "Iniciar sesión con Google",
"sign_in_apple": "Iniciar sesión con Apple",
"social_login_failed": "Error de inicio de sesión social. Inténtalo de nuevo."
```

For `de.json`:
```json
"or_continue_with": "Oder weiter mit",
"sign_in_google": "Mit Google anmelden",
"sign_in_apple": "Mit Apple anmelden",
"social_login_failed": "Soziale Anmeldung fehlgeschlagen. Bitte versuchen Sie es erneut."
```

For `fr.json`:
```json
"or_continue_with": "Ou continuer avec",
"sign_in_google": "Se connecter avec Google",
"sign_in_apple": "Se connecter avec Apple",
"social_login_failed": "Échec de la connexion sociale. Veuillez réessayer."
```

For `pt.json`:
```json
"or_continue_with": "Ou continuar com",
"sign_in_google": "Entrar com Google",
"sign_in_apple": "Entrar com Apple",
"social_login_failed": "Falha no login social. Tente novamente."
```

For `eu.json`:
```json
"or_continue_with": "Edo jarraitu honekin",
"sign_in_google": "Google-rekin hasi saioa",
"sign_in_apple": "Apple-rekin hasi saioa",
"social_login_failed": "Saioa hasteko errorea. Saiatu berriro."
```

For `ca.json`:
```json
"or_continue_with": "O continuar amb",
"sign_in_google": "Inicia sessió amb Google",
"sign_in_apple": "Inicia sessió amb Apple",
"social_login_failed": "Error d'inici de sessió social. Torna-ho a provar."
```

For `gl.json`:
```json
"or_continue_with": "Ou continuar con",
"sign_in_google": "Iniciar sesión con Google",
"sign_in_apple": "Iniciar sesión con Apple",
"social_login_failed": "Erro de inicio de sesión social. Téntao de novo."
```

**Step 3: Commit**

```bash
git add client/src/i18n/locales/*.json
git commit -m "feat: add SSO translation keys for all 8 languages"
```

---

### Task 11: Frontend — Add SSO buttons to LoginView

**Files:**
- Modify: `client/src/views/public/LoginView.vue`

**Step 1: Add SSO buttons to template**

In `LoginView.vue`, add this after the `</form>` tag (after line 41) and before the "Don't have an account?" `div`:

```html
<div class="sso-divider">
  <span>{{ $t('auth.or_continue_with') }}</span>
</div>

<div class="sso-buttons">
  <button
    type="button"
    class="sso-btn sso-btn-google"
    :disabled="socialAuth.isLoading.value"
    @click="handleGoogleLogin"
  >
    <svg class="sso-icon" viewBox="0 0 24 24" width="20" height="20">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    {{ $t('auth.sign_in_google') }}
  </button>

  <button
    type="button"
    class="sso-btn sso-btn-apple"
    :disabled="socialAuth.isLoading.value"
    @click="handleAppleLogin"
  >
    <svg class="sso-icon" viewBox="0 0 24 24" width="20" height="20">
      <path fill="currentColor" d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
    </svg>
    {{ $t('auth.sign_in_apple') }}
  </button>
</div>

<div v-if="socialAuth.error.value" class="error-msg mb-4">
  {{ socialAuth.error.value }}
</div>
```

**Step 2: Update the `<script setup>` section**

Add the import:

```js
import { useSocialAuth } from '@/composables/useSocialAuth'
```

Add after `const errorMsg = ref('')`:

```js
const socialAuth = useSocialAuth()
```

Add the SSO handler functions:

```js
const handleGoogleLogin = async () => {
  errorMsg.value = ''
  const result = await socialAuth.loginWithGoogle()
  if (result?.success) {
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }
    const upgradeToken = route.query.upgrade_token
    const redirectUrl = route.query.redirect
    if (upgradeToken) {
      router.push(`/checkout/${upgradeToken}`)
    } else if (redirectUrl) {
      router.push(redirectUrl)
    } else {
      router.push('/dashboard')
    }
  }
}

const handleAppleLogin = async () => {
  errorMsg.value = ''
  const result = await socialAuth.loginWithApple()
  if (result?.success) {
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }
    const upgradeToken = route.query.upgrade_token
    const redirectUrl = route.query.redirect
    if (upgradeToken) {
      router.push(`/checkout/${upgradeToken}`)
    } else if (redirectUrl) {
      router.push(redirectUrl)
    } else {
      router.push('/dashboard')
    }
  }
}
```

**Step 3: Add SSO styles**

Add to the `<style scoped>` section:

```css
.sso-divider {
  display: flex;
  align-items: center;
  margin: 24px 0;
  gap: 12px;
}

.sso-divider::before,
.sso-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

.sso-divider span {
  font-size: 0.875rem;
  color: var(--text-secondary);
  white-space: nowrap;
}

.sso-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sso-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text);
}

.sso-btn:hover {
  background: var(--surface-hover, rgba(0, 0, 0, 0.05));
}

.sso-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.sso-icon {
  flex-shrink: 0;
}
```

**Step 4: Verify build**

```bash
cd client && npm run build
```

Expected: Build succeeds.

**Step 5: Commit**

```bash
git add client/src/views/public/LoginView.vue
git commit -m "feat: add Google/Apple SSO buttons to LoginView"
```

---

### Task 12: Frontend — Add SSO buttons to RegisterView

**Files:**
- Modify: `client/src/views/public/RegisterView.vue`

**Step 1: Add the same SSO divider and buttons**

In `RegisterView.vue`, add after `</form>` (after line 85) and before the "Already have an account?" `div`:

Same HTML as LoginView:

```html
<div class="sso-divider">
  <span>{{ $t('auth.or_continue_with') }}</span>
</div>

<div class="sso-buttons">
  <button
    type="button"
    class="sso-btn sso-btn-google"
    :disabled="socialAuth.isLoading.value"
    @click="handleGoogleLogin"
  >
    <svg class="sso-icon" viewBox="0 0 24 24" width="20" height="20">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    {{ $t('auth.sign_in_google') }}
  </button>

  <button
    type="button"
    class="sso-btn sso-btn-apple"
    :disabled="socialAuth.isLoading.value"
    @click="handleAppleLogin"
  >
    <svg class="sso-icon" viewBox="0 0 24 24" width="20" height="20">
      <path fill="currentColor" d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
    </svg>
    {{ $t('auth.sign_in_apple') }}
  </button>
</div>

<div v-if="socialAuth.error.value" class="error-msg mb-4">
  {{ socialAuth.error.value }}
</div>
```

**Step 2: Update script setup**

Add import:

```js
import { useSocialAuth } from '@/composables/useSocialAuth'
```

Add after `const errorMsg = ref('')`:

```js
const socialAuth = useSocialAuth()
```

Add handlers:

```js
const handleGoogleLogin = async () => {
  errorMsg.value = ''
  const result = await socialAuth.loginWithGoogle()
  if (result?.success) {
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }
    const upgradeToken = route.query.upgrade_token
    if (upgradeToken) {
      router.push(`/checkout/${upgradeToken}`)
    } else {
      router.push('/dashboard')
    }
  }
}

const handleAppleLogin = async () => {
  errorMsg.value = ''
  const result = await socialAuth.loginWithApple()
  if (result?.success) {
    if (authStore.user?.language) {
      await setLocale(authStore.user.language)
    }
    const upgradeToken = route.query.upgrade_token
    if (upgradeToken) {
      router.push(`/checkout/${upgradeToken}`)
    } else {
      router.push('/dashboard')
    }
  }
}
```

**Step 3: Add SSO styles**

Add the same `.sso-divider`, `.sso-buttons`, `.sso-btn`, `.sso-icon` styles as in LoginView.

**Step 4: Verify build**

```bash
cd client && npm run build
```

Expected: Build succeeds.

**Step 5: Commit**

```bash
git add client/src/views/public/RegisterView.vue
git commit -m "feat: add Google/Apple SSO buttons to RegisterView"
```

---

### Task 13: Frontend — Add Vite env vars for SSO client IDs

**Files:**
- Modify: `client/.env.example` (create if doesn't exist)

**Step 1: Create/update env example**

Create `client/.env` (or `.env.local`) with:

```
VITE_GOOGLE_CLIENT_ID=
VITE_APPLE_SERVICE_ID=
```

Create `client/.env.example`:

```
VITE_GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
VITE_APPLE_SERVICE_ID=com.your.service.id
```

**Step 2: Commit**

```bash
git add client/.env.example
git commit -m "feat: add env example for SSO client IDs"
```

---

### Task 14: Full Test Run & Code Quality

**Step 1: Run all backend tests**

```bash
composer test
```

Expected: All tests pass.

**Step 2: Run code quality checks**

```bash
composer cs-check
```

Expected: No violations. Fix with `composer cs-fix` if needed.

**Step 3: Run frontend build**

```bash
cd client && npm run build
```

Expected: Build succeeds.

**Step 4: Final commit if any fixes were needed**

```bash
git add -A
git commit -m "fix: resolve code quality issues from SSO implementation"
```
