# Email Templates & Account Activation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add branded email templates, email verification workflow with HMAC-signed URLs, welcome emails for normal and SSO signup, and allow subscription purchase before activation.

**Architecture:** HMAC-signed URLs (stateless, no extra DB table) with `email_verified_at` timestamp on users. CakePHP Mailer class handles all emails with branded HTML layout. Frontend route guard blocks unverified users from dashboard but allows Stripe checkout. CakePHP `__()` i18n for backend email translations, Vue i18n for frontend.

**Tech Stack:** CakePHP 5.3 (Mailer, Migrations, i18n), Vue 3 (Composition API, Pinia, Vue Router), PHPUnit, Vite

---

### Task 1: Database Migration — Add `email_verified_at` to `users`

**Files:**
- Create: `config/Migrations/20260305120000_AddEmailVerifiedAtToUsers.php`

**Step 1: Create the migration**

```php
<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddEmailVerifiedAtToUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('email_verified_at', 'timestamp', [
            'default' => null,
            'null' => true,
            'after' => 'marketing_optin',
        ]);
        $table->update();

        // Backfill: treat all existing users as verified
        $this->execute("UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL");
    }
}
```

**Step 2: Run the migration**

Run: `bin/cake migrations migrate`
Expected: Migration applies successfully

**Step 3: Commit**

```bash
git add config/Migrations/20260305120000_AddEmailVerifiedAtToUsers.php
git commit -m "feat: add email_verified_at column to users table"
```

---

### Task 2: Update User Entity & Table — Expose `email_verified_at`

**Files:**
- Modify: `src/Model/Entity/User.php` (line 13, `_accessible` array)

**Step 1: Add `email_verified_at` to accessible fields**

In `src/Model/Entity/User.php`, add to the `$_accessible` array after `'marketing_optin' => true,`:

```php
'email_verified_at' => true,
```

**Step 2: Run existing tests to verify no regression**

Run: `composer test`
Expected: All tests pass

**Step 3: Commit**

```bash
git add src/Model/Entity/User.php
git commit -m "feat: expose email_verified_at on User entity"
```

---

### Task 3: EmailVerificationService — Signed URL Generation & Verification

**Files:**
- Create: `src/Service/EmailVerificationService.php`
- Create: `tests/TestCase/Service/EmailVerificationServiceTest.php`

**Step 1: Write the failing tests**

```php
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
        // Create a URL that expired 1 second ago
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
        $expectedMin = time() + (7 * 24 * 3600) - 5; // 5s tolerance
        $expectedMax = time() + (7 * 24 * 3600) + 5;

        $this->assertGreaterThanOrEqual($expectedMin, $expiry);
        $this->assertLessThanOrEqual($expectedMax, $expiry);
    }
}
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/TestCase/Service/EmailVerificationServiceTest.php`
Expected: FAIL — class not found

**Step 3: Implement EmailVerificationService**

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Routing\Router;

class EmailVerificationService
{
    private const EXPIRY_SECONDS = 7 * 24 * 3600; // 7 days

    /**
     * Generate a signed email verification URL.
     */
    public function generateSignedUrl(string $userId): string
    {
        $expiry = (string)(time() + self::EXPIRY_SECONDS);
        $sig = $this->sign($userId, $expiry);

        $baseUrl = Configure::read('App.fullBaseUrl', Router::fullBaseUrl());

        return $baseUrl . '/verify-email?' . http_build_query([
            'uid' => $userId,
            'exp' => $expiry,
            'sig' => $sig,
        ]);
    }

    /**
     * Verify a signed URL. Returns user ID if valid, null if invalid/expired.
     */
    public function verifySignedUrl(string $uid, string $exp, string $sig): ?string
    {
        // Check expiry
        if ((int)$exp < time()) {
            return null;
        }

        // Verify signature
        $expectedSig = $this->sign($uid, $exp);
        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        return $uid;
    }

    private function sign(string $userId, string $expiry): string
    {
        return hash_hmac(
            'sha256',
            $userId . ':' . $expiry,
            Configure::read('Security.salt'),
        );
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/TestCase/Service/EmailVerificationServiceTest.php`
Expected: All 6 tests pass

**Step 5: Run code quality checks**

Run: `composer cs-check`
Expected: No errors

**Step 6: Commit**

```bash
git add src/Service/EmailVerificationService.php tests/TestCase/Service/EmailVerificationServiceTest.php
git commit -m "feat: add EmailVerificationService with HMAC signed URLs"
```

---

### Task 4: Branded Email Layout Template

**Files:**
- Create: `templates/email/html/branded.php`

**Step 1: Create the branded HTML email layout**

This is a shared layout used by all email templates. It receives `$content` from the view and wraps it in branded chrome. The layout uses all inline CSS for email client compatibility.

```php
<?php
/**
 * Branded email layout for TriggerTime.
 *
 * @var \Cake\View\View $this
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="<?= $this->get('locale', 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TriggerTime</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:Inter,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;">
        <tr>
            <td align="center" style="padding:24px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0A0A0F;padding:24px 32px;border-radius:12px 12px 0 0;text-align:center;">
                            <span style="font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#C1FF72;letter-spacing:0.5px;">TriggerTime</span>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="background-color:#ffffff;padding:40px 32px;">
                            <?= $content ?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f8fa;padding:24px 32px;border-radius:0 0 12px 12px;text-align:center;border-top:1px solid #e8e8eb;">
                            <p style="margin:0 0 8px;font-size:13px;color:#8A8A9A;">
                                &copy; <?= date('Y') ?> TriggerTime. <?= __('All rights reserved.') ?>
                            </p>
                            <p style="margin:0;font-size:12px;color:#a0a0ad;">
                                <a href="https://triggertime.es/privacy" style="color:#8A8A9A;text-decoration:underline;"><?= __('Privacy Policy') ?></a>
                                &nbsp;&middot;&nbsp;
                                <a href="https://triggertime.es/terms" style="color:#8A8A9A;text-decoration:underline;"><?= __('Terms of Service') ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```

**Step 2: Commit**

```bash
git add templates/email/html/branded.php
git commit -m "feat: add branded email HTML layout"
```

---

### Task 5: Email Content Templates

**Files:**
- Create: `templates/email/html/welcome_activation.php`
- Create: `templates/email/html/welcome_sso.php`
- Create: `templates/email/html/password_reset.php`

**Step 1: Create welcome + activation template**

`templates/email/html/welcome_activation.php`:

```php
<?php
/**
 * Welcome + Email Activation template.
 *
 * @var \Cake\View\View $this
 * @var string $activationUrl
 * @var string $firstName
 */
$name = !empty($firstName) ? $firstName : __('there');
?>
<h1 style="margin:0 0 16px;font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#1a1a2e;">
    <?= __('Welcome to TriggerTime!') ?>
</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Hi {0}, thanks for creating your account.', h($name)) ?>
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Please verify your email address by clicking the button below to activate your account:') ?>
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td style="background-color:#C1FF72;border-radius:12px;">
            <a href="<?= h($activationUrl) ?>" style="display:inline-block;padding:14px 32px;font-family:Inter,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;color:#0A0A0F;text-decoration:none;">
                <?= __('Activate Your Account') ?>
            </a>
        </td>
    </tr>
</table>
<p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#8A8A9A;">
    <?= __('This link expires in 7 days. If you did not create an account, you can safely ignore this email.') ?>
</p>
<p style="margin:0;font-size:13px;color:#a0a0ad;word-break:break-all;">
    <?= __('If the button doesn\'t work, copy and paste this URL into your browser:') ?><br>
    <a href="<?= h($activationUrl) ?>" style="color:#C1693C;"><?= h($activationUrl) ?></a>
</p>
```

**Step 2: Create welcome SSO template**

`templates/email/html/welcome_sso.php`:

```php
<?php
/**
 * Welcome email for SSO users (no activation needed).
 *
 * @var \Cake\View\View $this
 * @var string $dashboardUrl
 * @var string $firstName
 * @var string $provider
 */
$name = !empty($firstName) ? $firstName : __('there');
?>
<h1 style="margin:0 0 16px;font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#1a1a2e;">
    <?= __('Welcome to TriggerTime!') ?>
</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Hi {0}, your account has been created using {1}.', h($name), h(ucfirst($provider))) ?>
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('You\'re all set! Start exploring your dashboard to manage your devices and subscriptions.') ?>
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td style="background-color:#C1FF72;border-radius:12px;">
            <a href="<?= h($dashboardUrl) ?>" style="display:inline-block;padding:14px 32px;font-family:Inter,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;color:#0A0A0F;text-decoration:none;">
                <?= __('Go to Dashboard') ?>
            </a>
        </td>
    </tr>
</table>
```

**Step 3: Create password reset template**

`templates/email/html/password_reset.php`:

```php
<?php
/**
 * Password reset email template.
 *
 * @var \Cake\View\View $this
 * @var string $resetUrl
 * @var string $firstName
 */
$name = !empty($firstName) ? $firstName : __('there');
?>
<h1 style="margin:0 0 16px;font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#1a1a2e;">
    <?= __('Reset Your Password') ?>
</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Hi {0}, we received a request to reset your password.', h($name)) ?>
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Click the button below to set a new password:') ?>
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td style="background-color:#C1FF72;border-radius:12px;">
            <a href="<?= h($resetUrl) ?>" style="display:inline-block;padding:14px 32px;font-family:Inter,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;color:#0A0A0F;text-decoration:none;">
                <?= __('Reset Password') ?>
            </a>
        </td>
    </tr>
</table>
<p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#8A8A9A;">
    <?= __('This link expires in 24 hours. If you didn\'t request a password reset, you can safely ignore this email.') ?>
</p>
<p style="margin:0;font-size:13px;color:#a0a0ad;word-break:break-all;">
    <?= __('If the button doesn\'t work, copy and paste this URL into your browser:') ?><br>
    <a href="<?= h($resetUrl) ?>" style="color:#C1693C;"><?= h($resetUrl) ?></a>
</p>
```

**Step 4: Commit**

```bash
git add templates/email/html/welcome_activation.php templates/email/html/welcome_sso.php templates/email/html/password_reset.php
git commit -m "feat: add branded email content templates"
```

---

### Task 6: UserMailer Class

**Files:**
- Create: `src/Mailer/UserMailer.php`
- Create: `tests/TestCase/Mailer/UserMailerTest.php`

**Step 1: Write the failing tests**

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Mailer;

use App\Mailer\UserMailer;
use App\Model\Entity\User;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;

class UserMailerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Use debug transport to capture emails without sending
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => 'Debug']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TransportFactory::drop('default');
    }

    private function makeUser(array $overrides = []): User
    {
        $user = new User();
        $user->id = 'user-uuid-123';
        $user->email = 'test@example.com';
        $user->first_name = 'John';
        $user->language = 'en';
        foreach ($overrides as $key => $value) {
            $user->{$key} = $value;
        }

        return $user;
    }

    public function testWelcomeActivationSetsCorrectSubject(): void
    {
        $mailer = new UserMailer();
        $mailer->welcomeActivation($this->makeUser(), 'https://triggertime.es/verify-email?uid=x&exp=y&sig=z');

        $this->assertSame(['test@example.com' => 'test@example.com'], $mailer->getTo());
        $this->assertStringContainsString('TriggerTime', $mailer->getSubject());
    }

    public function testWelcomeSsoSetsCorrectSubject(): void
    {
        $mailer = new UserMailer();
        $mailer->welcomeSso($this->makeUser(), 'google');

        $this->assertSame(['test@example.com' => 'test@example.com'], $mailer->getTo());
        $this->assertStringContainsString('TriggerTime', $mailer->getSubject());
    }

    public function testPasswordResetSetsCorrectSubject(): void
    {
        $mailer = new UserMailer();
        $mailer->passwordReset($this->makeUser(), 'https://triggertime.es/reset-password/token123');

        $this->assertSame(['test@example.com' => 'test@example.com'], $mailer->getTo());
        $this->assertStringContainsString('TriggerTime', $mailer->getSubject());
    }
}
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/TestCase/Mailer/UserMailerTest.php`
Expected: FAIL — class not found

**Step 3: Implement UserMailer**

```php
<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\I18n\I18n;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class UserMailer extends Mailer
{
    /**
     * Welcome + email activation for normal signups.
     */
    public function welcomeActivation(User $user, string $activationUrl): void
    {
        $previousLocale = I18n::getLocale();
        I18n::setLocale($user->language ?? 'en');

        $this
            ->setTo($user->email)
            ->setSubject(__('TriggerTime - {0}', __('Activate Your Account')))
            ->setViewVars([
                'activationUrl' => $activationUrl,
                'firstName' => $user->first_name,
                'locale' => $user->language ?? 'en',
            ])
            ->viewBuilder()
            ->setTemplate('welcome_activation')
            ->setLayout('branded');

        I18n::setLocale($previousLocale);
    }

    /**
     * Welcome email for SSO users (no activation needed).
     */
    public function welcomeSso(User $user, string $provider): void
    {
        $previousLocale = I18n::getLocale();
        I18n::setLocale($user->language ?? 'en');

        $baseUrl = env('FRONTEND_URL', Router::fullBaseUrl());

        $this
            ->setTo($user->email)
            ->setSubject(__('TriggerTime - {0}', __('Welcome!')))
            ->setViewVars([
                'dashboardUrl' => $baseUrl . '/dashboard',
                'firstName' => $user->first_name,
                'provider' => $provider,
                'locale' => $user->language ?? 'en',
            ])
            ->viewBuilder()
            ->setTemplate('welcome_sso')
            ->setLayout('branded');

        I18n::setLocale($previousLocale);
    }

    /**
     * Password reset email.
     */
    public function passwordReset(User $user, string $resetUrl): void
    {
        $previousLocale = I18n::getLocale();
        I18n::setLocale($user->language ?? 'en');

        $this
            ->setTo($user->email)
            ->setSubject(__('TriggerTime - {0}', __('Reset Your Password')))
            ->setViewVars([
                'resetUrl' => $resetUrl,
                'firstName' => $user->first_name,
                'locale' => $user->language ?? 'en',
            ])
            ->viewBuilder()
            ->setTemplate('password_reset')
            ->setLayout('branded');

        I18n::setLocale($previousLocale);
    }
}
```

**Step 4: Move the branded layout to the layout directory**

CakePHP Mailer looks for layouts in `templates/layout/email/html/`. The branded layout must be placed there:

- Move `templates/email/html/branded.php` → `templates/layout/email/html/branded.php`

Create directory if needed: `mkdir -p templates/layout/email/html`

**Step 5: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/TestCase/Mailer/UserMailerTest.php`
Expected: All 3 tests pass

**Step 6: Run code quality checks**

Run: `composer cs-check`
Expected: No errors

**Step 7: Commit**

```bash
git add src/Mailer/UserMailer.php tests/TestCase/Mailer/UserMailerTest.php templates/layout/email/html/branded.php
git commit -m "feat: add UserMailer with branded email templates"
```

---

### Task 7: Backend Routes — Add verify-email and resend-verification

**Files:**
- Modify: `config/routes.php`

**Step 1: Add the new public and authenticated routes**

In `config/routes.php`, inside the `$v1->prefix('Web', ...)` callback, add `verify-email` to the public section (after the `social-login` route, around line 72) and `resend-verification` to the authenticated section.

Add to the **public endpoints** section (after `$web->post('/auth/social-login', ...)`):

```php
$web->get('/auth/verify-email', ['controller' => 'Auth', 'action' => 'verifyEmail']);
```

Add to the **authenticated endpoints** section (inside the `$webAuth` scope, after the existing auth routes):

```php
$webAuth->post('/auth/resend-verification', ['controller' => 'Auth', 'action' => 'resendVerification']);
```

**Step 2: Run tests to verify no regression**

Run: `composer test`
Expected: All tests pass

**Step 3: Commit**

```bash
git add config/routes.php
git commit -m "feat: add verify-email and resend-verification routes"
```

---

### Task 8: AuthController — Verify Email Endpoint

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php`

**Step 1: Write the failing test**

Add to `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php`:

```php
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
    // Register a user first
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

    // The user should be unverified
    $this->assertNull($body['user']['email_verified_at']);

    // Generate a valid signed URL
    $service = new \App\Service\EmailVerificationService();
    $url = $service->generateSignedUrl($userId);
    $parsed = parse_url($url);
    parse_str($parsed['query'], $query);

    // Hit the verify endpoint
    $this->get('/api/v1/web/auth/verify-email?' . http_build_query($query));

    // Should redirect (302) to the SPA with ?verified=1
    $this->assertResponseCode(302);
    $this->assertHeaderContains('Location', 'verified=1');
}
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php --filter testVerifyEmail`
Expected: FAIL — method not found

**Step 3: Add `verifyEmail` action to AuthController**

Add this method to `src/Controller/Api/V1/Web/AuthController.php`. Also add the necessary `use` import at the top: `use App\Service\EmailVerificationService;` and `use Cake\I18n\DateTime;` (already imported).

```php
/**
 * Verify a user's email via HMAC-signed URL.
 *
 * GET /api/v1/web/auth/verify-email?uid=...&exp=...&sig=...
 */
public function verifyEmail()
{
    $this->request->allowMethod(['get']);

    $uid = $this->request->getQuery('uid');
    $exp = $this->request->getQuery('exp');
    $sig = $this->request->getQuery('sig');

    if (!$uid || !$exp || !$sig) {
        throw new BadRequestException('Missing verification parameters');
    }

    $service = new EmailVerificationService();
    $userId = $service->verifySignedUrl((string)$uid, (string)$exp, (string)$sig);

    if (!$userId) {
        throw new BadRequestException('Invalid or expired verification link');
    }

    $user = $this->Authentication->find()->where(['id' => $userId])->first();
    if (!$user) {
        throw new BadRequestException('User not found');
    }

    // Only verify if not already verified
    if ($user->email_verified_at === null) {
        $user->email_verified_at = DateTime::now();
        $this->Authentication->save($user);
    }

    // Redirect to SPA with verified flag
    $frontendUrl = env('FRONTEND_URL', \Cake\Routing\Router::fullBaseUrl());

    return $this->redirect($frontendUrl . '/dashboard?verified=1');
}
```

**Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php --filter testVerifyEmail`
Expected: All 3 tests pass

**Step 5: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
git commit -m "feat: add verifyEmail endpoint with signed URL validation"
```

---

### Task 9: AuthController — Resend Verification Endpoint

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php`

**Step 1: Write the failing test**

Add to `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php`:

```php
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
    // Register a user to get a token
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

    // Request resend
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
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php --filter testResendVerification`
Expected: FAIL — missing route or method

**Step 3: Add `resendVerification` action to AuthController**

Add this method and the import `use App\Mailer\UserMailer;` at the top of the file:

```php
/**
 * Resend the email verification link.
 *
 * Rate limited: stores last_verification_sent_at in session/cache conceptually.
 * Simple approach: check if user already verified, then send.
 */
public function resendVerification()
{
    $this->request->allowMethod(['post']);
    $payload = $this->request->getAttribute('jwt_payload');
    if (!$payload) {
        throw new UnauthorizedException('Missing or invalid token payload');
    }

    $user = $this->Authentication->get($payload['sub']);

    if ($user->email_verified_at !== null) {
        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Email already verified',
            ]));
    }

    $verificationService = new EmailVerificationService();
    $activationUrl = $verificationService->generateSignedUrl($user->id);

    try {
        $mailer = new UserMailer();
        $mailer->welcomeActivation($user, $activationUrl);
        $mailer->deliver();
    } catch (Exception $e) {
        Log::error('Verification email failed: ' . $e->getMessage());
    }

    return $this->response->withType('application/json')
        ->withStringBody((string)json_encode([
            'success' => true,
            'message' => 'Verification email sent',
        ]));
}
```

**Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php --filter testResendVerification`
Expected: Both tests pass

**Step 5: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
git commit -m "feat: add resendVerification endpoint"
```

---

### Task 10: Modify Registration — Send Welcome Email & Set Unverified

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php` (register method, ~line 77-146)

**Step 1: Update the register test to verify email_verified_at is null**

Add to `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php`:

```php
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
```

**Step 2: Modify the `register()` method in AuthController**

After the user is saved and before the JWT is generated (~after the `$subs->save($sub)` call), add the email sending code. The user is already created with `email_verified_at = null` by default (the column is nullable).

Add after the B2B license linking block and before the JWT generation:

```php
// Send welcome + activation email
$verificationService = new EmailVerificationService();
$activationUrl = $verificationService->generateSignedUrl($user->id);

try {
    $mailer = new UserMailer();
    $mailer->welcomeActivation($user, $activationUrl);
    $mailer->deliver();
} catch (Exception $e) {
    Log::error('Welcome email failed: ' . $e->getMessage());
}
```

Make sure the `use App\Service\EmailVerificationService;` and `use App\Mailer\UserMailer;` imports are present at the top of the file.

**Step 3: Run the test**

Run: `vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php --filter testRegisterReturnsUnverifiedUser`
Expected: PASS

**Step 4: Run all tests to verify no regression**

Run: `composer test`
Expected: All tests pass

**Step 5: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
git commit -m "feat: send welcome+activation email on registration"
```

---

### Task 11: Modify Social Login — Auto-verify & Send Welcome Email

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php` (socialLogin method, ~line 296-406)

**Step 1: Update the socialLogin method**

In the `socialLogin()` method, in the "Create new user" branch (the `else` block where a new user is created, ~line 357), add `email_verified_at` and send welcome email.

After `$user->marketing_optin = ...` and before `if (!$this->Authentication->save($user))`:

```php
$user->email_verified_at = DateTime::now();
```

After the subscription creation and B2B license linking (but before the closing `}` of the new user creation block), add:

```php
// Send welcome email for SSO users
try {
    $mailer = new UserMailer();
    $mailer->welcomeSso($user, $provider);
    $mailer->deliver();
} catch (Exception $e) {
    Log::error('SSO welcome email failed: ' . $e->getMessage());
}
```

**Step 2: Run all tests**

Run: `composer test`
Expected: All tests pass

**Step 3: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php
git commit -m "feat: auto-verify SSO users and send welcome email"
```

---

### Task 12: Refactor forgotPassword — Use UserMailer

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php` (forgotPassword method, ~line 204-250)

**Step 1: Replace the inline email with UserMailer**

In the `forgotPassword()` method, replace the email sending block:

```php
// OLD (remove this):
try {
    $mailer = new Mailer('default');
    $mailer->setTo($user->email)
        ->setSubject('TriggerTime - Password Reset')
        ->deliver(
            "You requested a password reset. Please click on the link below to set a new password:\n\n"
            . $resetLink,
        );
} catch (Exception $e) {
    // Log and continue, or fail depending on strictness
    Log::error('Mail sending failed: ' . $e->getMessage());
}
```

```php
// NEW (replace with this):
try {
    $userMailer = new UserMailer();
    $userMailer->passwordReset($user, $resetLink);
    $userMailer->deliver();
} catch (Exception $e) {
    Log::error('Password reset email failed: ' . $e->getMessage());
}
```

Also remove the now-unused `use Cake\Mailer\Mailer;` import from the top of the file (since `UserMailer` extends `Mailer` and is used directly instead).

**Step 2: Run all tests**

Run: `composer test`
Expected: All tests pass

**Step 3: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php
git commit -m "refactor: use UserMailer for password reset email"
```

---

### Task 13: Frontend — Auth API & Store Updates

**Files:**
- Modify: `client/src/api/auth.js`
- Modify: `client/src/stores/auth.js`

**Step 1: Add `resendVerification` to auth API**

In `client/src/api/auth.js`, add after the `resetPassword` method:

```javascript
resendVerification() {
    return api.post('/web/auth/resend-verification');
},
```

**Step 2: Add `isVerified` computed to auth store**

In `client/src/stores/auth.js`:

Add after the `isFree` computed (~line 16):

```javascript
const isVerified = computed(() => !!user.value?.email_verified_at)
```

Add `isVerified` to the return object (~line 89-104):

```javascript
return {
    token,
    user,
    subscription,
    isAuthenticated,
    isAdmin,
    isClubAdmin,
    isProPlus,
    isFree,
    isVerified,
    login,
    register,
    socialLogin,
    fetchUser,
    setAuthData,
    logout
}
```

**Step 3: Commit**

```bash
git add client/src/api/auth.js client/src/stores/auth.js
git commit -m "feat: add resendVerification API and isVerified computed"
```

---

### Task 14: Frontend — i18n Keys for Verification

**Files:**
- Modify: `client/src/i18n/locales/en.json`
- Modify: `client/src/i18n/locales/es.json`
- Modify remaining locale files: `ca.json`, `de.json`, `fr.json`, `pt.json`, `eu.json`, `gl.json`

**Step 1: Add verification keys to English**

Add to the `"auth"` section of `en.json` (after `"account_deactivated"` key):

```json
"verify_email_title": "Verify Your Email",
"verify_email_subtitle": "We've sent a verification link to your email address. Please check your inbox and click the link to activate your account.",
"verify_email_sent_to": "Verification email sent to {email}",
"verify_email_resend": "Resend Verification Email",
"verify_email_resend_success": "Verification email resent!",
"verify_email_resend_cooldown": "Please wait before requesting another email",
"verify_email_check_spam": "Didn't receive it? Check your spam folder or click resend.",
"verify_email_success": "Your email has been verified! Welcome to TriggerTime.",
"verify_email_subscribe_cta": "Subscribe to Pro+ while you wait"
```

**Step 2: Add verification keys to Spanish**

Add to the `"auth"` section of `es.json`:

```json
"verify_email_title": "Verifica tu Email",
"verify_email_subtitle": "Hemos enviado un enlace de verificación a tu dirección de email. Revisa tu bandeja de entrada y haz clic en el enlace para activar tu cuenta.",
"verify_email_sent_to": "Email de verificación enviado a {email}",
"verify_email_resend": "Reenviar Email de Verificación",
"verify_email_resend_success": "¡Email de verificación reenviado!",
"verify_email_resend_cooldown": "Por favor espera antes de solicitar otro email",
"verify_email_check_spam": "¿No lo recibiste? Revisa tu carpeta de spam o haz clic en reenviar.",
"verify_email_success": "¡Tu email ha sido verificado! Bienvenido a TriggerTime.",
"verify_email_subscribe_cta": "Suscríbete a Pro+ mientras esperas"
```

**Step 3: Add keys to remaining locales** (use English as placeholder for non-translated locales — can be translated later):

For each of `ca.json`, `de.json`, `fr.json`, `pt.json`, `eu.json`, `gl.json`, add the same keys from the English version to the `"auth"` section. Use English text as fallback.

**Step 4: Commit**

```bash
git add client/src/i18n/locales/*.json
git commit -m "feat: add email verification i18n keys for all locales"
```

---

### Task 15: Frontend — VerifyEmailView Component

**Files:**
- Create: `client/src/views/public/VerifyEmailView.vue`

**Step 1: Create the verification pending page**

```vue
<template>
  <div class="container auth-container">
    <AppCard class="auth-card">
      <div class="text-center mb-8">
        <div class="email-icon mb-4">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
        </div>
        <h1 class="mb-2">{{ $t('auth.verify_email_title') }}</h1>
        <p class="text-secondary">{{ $t('auth.verify_email_subtitle') }}</p>
      </div>

      <div v-if="authStore.user?.email" class="email-badge mb-6">
        {{ authStore.user.email }}
      </div>

      <div v-if="successMsg" class="success-msg mb-4">
        {{ successMsg }}
      </div>

      <div v-if="errorMsg" class="error-msg mb-4">
        {{ errorMsg }}
      </div>

      <AppButton
        class="w-full mb-4"
        :loading="isResending"
        :disabled="cooldown > 0"
        @click="handleResend"
      >
        {{ cooldown > 0 ? `${$t('auth.verify_email_resend')} (${cooldown}s)` : $t('auth.verify_email_resend') }}
      </AppButton>

      <p class="text-center text-sm text-secondary mb-6">
        {{ $t('auth.verify_email_check_spam') }}
      </p>

      <div v-if="showSubscribeCta" class="subscribe-cta">
        <hr class="divider mb-4" />
        <p class="text-center text-sm text-secondary mb-4">
          {{ $t('auth.verify_email_subscribe_cta') }}
        </p>
        <AppButton
          variant="secondary"
          class="w-full"
          @click="handleSubscribe"
        >
          {{ $t('dashboard.upgrade_pro') }}
        </AppButton>
      </div>

      <div class="text-center mt-6">
        <button class="logout-link" @click="handleLogout">
          {{ $t('common.logout') }}
        </button>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import { subscriptionsApi } from '@/api/subscriptions'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const isResending = ref(false)
const cooldown = ref(0)
const successMsg = ref('')
const errorMsg = ref('')
const showSubscribeCta = ref(!!route.query.intent || !!route.query.upgrade_token)

let cooldownTimer = null

const handleResend = async () => {
  if (cooldown.value > 0) return

  isResending.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const response = await authApi.resendVerification()
    if (response.success) {
      successMsg.value = response.message
      startCooldown()
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Failed to resend verification email.'
  } finally {
    isResending.value = false
  }
}

const startCooldown = () => {
  cooldown.value = 60
  cooldownTimer = setInterval(() => {
    cooldown.value--
    if (cooldown.value <= 0) {
      clearInterval(cooldownTimer)
    }
  }, 1000)
}

const handleSubscribe = async () => {
  try {
    const upgradeToken = route.query.upgrade_token
    const response = await subscriptionsApi.createCheckout(upgradeToken || null)
    if (response.checkout_url) {
      window.location.href = response.checkout_url
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Failed to start checkout.'
  }
}

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}

onMounted(() => {
  // Start with initial cooldown to prevent immediate resend after registration
  startCooldown()
})
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px);
  padding: 40px 20px;
}

.auth-card {
  width: 100%;
  max-width: 480px;
  padding: 40px 32px;
}

.email-icon {
  display: flex;
  justify-content: center;
}

.email-badge {
  text-align: center;
  padding: 10px 16px;
  background: var(--bg-elevated);
  border-radius: 8px;
  font-size: 0.9375rem;
  color: var(--text-primary);
  font-weight: 500;
}

.success-msg {
  color: var(--success);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(74, 222, 128, 0.1);
  border-radius: 8px;
}

.error-msg {
  color: var(--danger);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(255, 77, 77, 0.1);
  border-radius: 8px;
}

.divider {
  border: none;
  border-top: 1px solid var(--border-subtle);
}

.logout-link {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  font-size: 0.875rem;
  text-decoration: underline;
}

.logout-link:hover {
  color: var(--text-primary);
}

.w-full { width: 100%; }
.text-center { text-align: center; }
.text-secondary { color: var(--text-secondary); }
</style>
```

**Step 2: Commit**

```bash
git add client/src/views/public/VerifyEmailView.vue
git commit -m "feat: add VerifyEmailView component"
```

---

### Task 16: Frontend — Router Guard for Email Verification

**Files:**
- Modify: `client/src/router/index.js`

**Step 1: Add the verify-email route**

Add after the `reset-password` route (~line 63, before the `upgrade` route):

```javascript
{
    path: '/verify-email',
    name: 'VerifyEmail',
    component: () => import('../views/public/VerifyEmailView.vue'),
    meta: { requiresAuth: true, title: 'Verify Email' }
},
```

**Step 2: Add `requiresVerified` meta to dashboard routes**

Update each dashboard route to include `requiresVerified: true`:

```javascript
// Dashboard route (~line 88)
meta: { requiresAuth: true, requiresVerified: true, title: 'Dashboard' }

// Subscription route (~line 97)
meta: { requiresAuth: true, requiresVerified: true, title: 'Subscription' }

// Devices route (~line 103)
meta: { requiresAuth: true, requiresVerified: true, title: 'My Devices' }

// Profile route (~line 109)
meta: { requiresAuth: true, requiresVerified: true, title: 'Profile' }

// Admin route (~line 116)
meta: { requiresAuth: true, requiresVerified: true, requiresAdminRole: true }
```

Also add `requiresVerified: true` to the Home route when `!isLandingDomain` (line 24):

```javascript
meta: { requiresAuth: !isLandingDomain, requiresVerified: !isLandingDomain }
```

**Step 3: Add the verification guard to `router.beforeEach`**

In the `router.beforeEach` callback (~line 209), after the `requiresAuth` check (line 226-229) and before the `requiresAdminRole` check, add:

```javascript
// Redirect unverified users to verification page
if (to.meta.requiresVerified && authStore.isAuthenticated && !authStore.isVerified) {
    // Allow access if user data hasn't loaded yet (isVerified will be false initially)
    if (authStore.user !== null) {
        return { name: 'VerifyEmail', query: to.query }
    }
}

// Redirect verified users away from verify-email page
if (to.name === 'VerifyEmail' && authStore.isAuthenticated && authStore.isVerified) {
    return { name: 'Dashboard' }
}
```

**Step 4: Run frontend build to check for errors**

Run (from `client/`): `npm run build`
Expected: Build succeeds

**Step 5: Commit**

```bash
git add client/src/router/index.js
git commit -m "feat: add email verification route guard and verify-email route"
```

---

### Task 17: Frontend — Redirect Registration to Verify Email

**Files:**
- Modify: `client/src/views/public/RegisterView.vue`

**Step 1: Update the registration success handler**

In `client/src/views/public/RegisterView.vue`, modify the `handleRegister` function (~line 187-196). Change the redirect after successful registration:

Replace:

```javascript
const upgradeToken = route.query.upgrade_token
if (upgradeToken) {
    router.push(`/checkout/${upgradeToken}`)
} else {
    router.push('/dashboard')
}
```

With:

```javascript
const upgradeToken = route.query.upgrade_token
if (upgradeToken) {
    router.push({ name: 'VerifyEmail', query: { upgrade_token: upgradeToken, intent: 'subscribe' } })
} else {
    router.push({ name: 'VerifyEmail' })
}
```

Note: SSO login handlers (`handleGoogleLogin`, `handleAppleLogin`) should keep their existing behavior — they redirect to `/dashboard` or `/checkout` directly since SSO users are auto-verified.

**Step 2: Run frontend build**

Run (from `client/`): `npm run build`
Expected: Build succeeds

**Step 3: Commit**

```bash
git add client/src/views/public/RegisterView.vue
git commit -m "feat: redirect normal registration to verify-email page"
```

---

### Task 18: Frontend — Handle `?verified=1` Success Toast

**Files:**
- Modify: `client/src/views/dashboard/DashboardHome.vue`

**Step 1: Check current DashboardHome structure**

Read `client/src/views/dashboard/DashboardHome.vue` to understand the current structure before modifying.

**Step 2: Add verification success handling**

In the `<script setup>` section, add:

```javascript
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const { t } = useI18n()
const verificationSuccess = ref(route.query.verified === '1')
```

In the template, add at the top of the content area:

```html
<div v-if="verificationSuccess" class="success-banner mb-6">
    {{ $t('auth.verify_email_success') }}
</div>
```

Add the corresponding style:

```css
.success-banner {
    color: var(--success);
    font-size: 0.9375rem;
    text-align: center;
    padding: 12px 16px;
    background: rgba(74, 222, 128, 0.1);
    border: 1px solid rgba(74, 222, 128, 0.2);
    border-radius: 8px;
}
```

Also call `authStore.fetchUser()` on mount if `verified=1` to refresh user data:

```javascript
onMounted(async () => {
    if (route.query.verified === '1') {
        await authStore.fetchUser()
    }
})
```

**Step 3: Run frontend build**

Run (from `client/`): `npm run build`
Expected: Build succeeds

**Step 4: Commit**

```bash
git add client/src/views/dashboard/DashboardHome.vue
git commit -m "feat: show verification success banner on dashboard"
```

---

### Task 19: Backend i18n — Email Translation Files

**Files:**
- Create: `src/Locale/en/default.po`
- Create: `src/Locale/es/default.po`
- Create: `src/Locale/ca/default.po`
- Create: `src/Locale/de/default.po`
- Create: `src/Locale/fr/default.po`
- Create: `src/Locale/pt/default.po`
- Create: `src/Locale/eu/default.po`
- Create: `src/Locale/gl/default.po`

**Step 1: Create the English translations (base)**

Create `src/Locale/en/default.po`:

```po
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

msgid "Welcome to TriggerTime!"
msgstr "Welcome to TriggerTime!"

msgid "Hi {0}, thanks for creating your account."
msgstr "Hi {0}, thanks for creating your account."

msgid "Please verify your email address by clicking the button below to activate your account:"
msgstr "Please verify your email address by clicking the button below to activate your account:"

msgid "Activate Your Account"
msgstr "Activate Your Account"

msgid "This link expires in 7 days. If you did not create an account, you can safely ignore this email."
msgstr "This link expires in 7 days. If you did not create an account, you can safely ignore this email."

msgid "If the button doesn't work, copy and paste this URL into your browser:"
msgstr "If the button doesn't work, copy and paste this URL into your browser:"

msgid "Hi {0}, your account has been created using {1}."
msgstr "Hi {0}, your account has been created using {1}."

msgid "You're all set! Start exploring your dashboard to manage your devices and subscriptions."
msgstr "You're all set! Start exploring your dashboard to manage your devices and subscriptions."

msgid "Go to Dashboard"
msgstr "Go to Dashboard"

msgid "Reset Your Password"
msgstr "Reset Your Password"

msgid "Hi {0}, we received a request to reset your password."
msgstr "Hi {0}, we received a request to reset your password."

msgid "Click the button below to set a new password:"
msgstr "Click the button below to set a new password:"

msgid "Reset Password"
msgstr "Reset Password"

msgid "This link expires in 24 hours. If you didn't request a password reset, you can safely ignore this email."
msgstr "This link expires in 24 hours. If you didn't request a password reset, you can safely ignore this email."

msgid "All rights reserved."
msgstr "All rights reserved."

msgid "Privacy Policy"
msgstr "Privacy Policy"

msgid "Terms of Service"
msgstr "Terms of Service"

msgid "there"
msgstr "there"

msgid "Welcome!"
msgstr "Welcome!"

msgid "TriggerTime - {0}"
msgstr "TriggerTime - {0}"
```

**Step 2: Create the Spanish translations**

Create `src/Locale/es/default.po`:

```po
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

msgid "Welcome to TriggerTime!"
msgstr "¡Bienvenido a TriggerTime!"

msgid "Hi {0}, thanks for creating your account."
msgstr "Hola {0}, gracias por crear tu cuenta."

msgid "Please verify your email address by clicking the button below to activate your account:"
msgstr "Por favor verifica tu dirección de email haciendo clic en el botón de abajo para activar tu cuenta:"

msgid "Activate Your Account"
msgstr "Activar Tu Cuenta"

msgid "This link expires in 7 days. If you did not create an account, you can safely ignore this email."
msgstr "Este enlace expira en 7 días. Si no creaste una cuenta, puedes ignorar este email."

msgid "If the button doesn't work, copy and paste this URL into your browser:"
msgstr "Si el botón no funciona, copia y pega esta URL en tu navegador:"

msgid "Hi {0}, your account has been created using {1}."
msgstr "Hola {0}, tu cuenta ha sido creada usando {1}."

msgid "You're all set! Start exploring your dashboard to manage your devices and subscriptions."
msgstr "¡Todo listo! Comienza a explorar tu panel para gestionar tus dispositivos y suscripciones."

msgid "Go to Dashboard"
msgstr "Ir al Panel"

msgid "Reset Your Password"
msgstr "Restablecer Tu Contraseña"

msgid "Hi {0}, we received a request to reset your password."
msgstr "Hola {0}, recibimos una solicitud para restablecer tu contraseña."

msgid "Click the button below to set a new password:"
msgstr "Haz clic en el botón de abajo para establecer una nueva contraseña:"

msgid "Reset Password"
msgstr "Restablecer Contraseña"

msgid "This link expires in 24 hours. If you didn't request a password reset, you can safely ignore this email."
msgstr "Este enlace expira en 24 horas. Si no solicitaste un restablecimiento de contraseña, puedes ignorar este email."

msgid "All rights reserved."
msgstr "Todos los derechos reservados."

msgid "Privacy Policy"
msgstr "Política de Privacidad"

msgid "Terms of Service"
msgstr "Términos de Servicio"

msgid "there"
msgstr "ahí"

msgid "Welcome!"
msgstr "¡Bienvenido!"

msgid "TriggerTime - {0}"
msgstr "TriggerTime - {0}"
```

**Step 3: Create placeholder files for remaining locales**

For `ca`, `de`, `fr`, `pt`, `eu`, `gl` — create `.po` files with the same structure as `en/default.po` (English fallback). These can be properly translated later.

**Step 4: Commit**

```bash
git add src/Locale/
git commit -m "feat: add email translation files for all supported locales"
```

---

### Task 20: Final Integration Testing & Code Quality

**Step 1: Run full backend test suite**

Run: `composer check`
Expected: All tests pass + code sniffer clean

**Step 2: Run PHPStan**

Run: `SECURITY_SALT=test-salt phpstan`
Expected: No errors at level 8

**Step 3: Run frontend build**

Run (from `client/`): `npm run build`
Expected: Build succeeds with no errors

**Step 4: Manual smoke test checklist**

1. Register new user → redirected to verify-email page → email received with branded template
2. Click activation link → redirected to dashboard with success banner
3. SSO login (Google/Apple) → lands directly on dashboard → welcome email received
4. Unverified user tries to access `/dashboard` → redirected to verify-email page
5. Resend verification email → cooldown timer starts → email received
6. Forgot password → branded password reset email received

**Step 5: Final commit**

```bash
git add -A
git commit -m "chore: final integration cleanup for email verification feature"
```

---

## Summary of All Files Changed/Created

### Created (Backend):
- `config/Migrations/20260305120000_AddEmailVerifiedAtToUsers.php`
- `src/Service/EmailVerificationService.php`
- `src/Mailer/UserMailer.php`
- `templates/layout/email/html/branded.php`
- `templates/email/html/welcome_activation.php`
- `templates/email/html/welcome_sso.php`
- `templates/email/html/password_reset.php`
- `tests/TestCase/Service/EmailVerificationServiceTest.php`
- `tests/TestCase/Mailer/UserMailerTest.php`
- `src/Locale/{en,es,ca,de,fr,pt,eu,gl}/default.po`

### Modified (Backend):
- `src/Model/Entity/User.php` — add `email_verified_at` to accessible
- `src/Controller/Api/V1/Web/AuthController.php` — add `verifyEmail`, `resendVerification`; modify `register`, `socialLogin`, `forgotPassword`
- `config/routes.php` — add new routes
- `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php` — add tests

### Created (Frontend):
- `client/src/views/public/VerifyEmailView.vue`

### Modified (Frontend):
- `client/src/api/auth.js` — add `resendVerification`
- `client/src/stores/auth.js` — add `isVerified` computed
- `client/src/router/index.js` — add verify-email route + guard
- `client/src/views/public/RegisterView.vue` — redirect to verify-email
- `client/src/views/dashboard/DashboardHome.vue` — verification success banner
- `client/src/i18n/locales/*.json` — add verification i18n keys
