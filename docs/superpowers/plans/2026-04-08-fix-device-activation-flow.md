# Fix Device Activation Flow — Preserve upgrade_token Across Email Verification

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When a user arrives from the mobile app via `/checkout/TOKEN`, creates an account, verifies their email, and purchases a subscription, the device must be linked automatically.

**Architecture:** Persist the `upgrade_token` string on the user record during registration. After email verification, the frontend checks for a pending token and redirects to the checkout flow instead of the dashboard. The existing Stripe webhook device-linking logic already handles the rest when `upgrade_token` is passed through checkout.

**Tech Stack:** CakePHP 5.3 (migration, controller changes), Vue 3 (frontend redirect logic), PHPUnit (tests)

---

## Current Broken Flow

```
App → /checkout/TOKEN → Register?upgrade_token=TOKEN → VerifyEmail?upgrade_token=TOKEN
                                                              ↓
                                              Email link: /verify-email?uid=&exp=&sig=
                                                              ↓
                                              ❌ upgrade_token LOST → /dashboard
```

## Fixed Flow

```
App → /checkout/TOKEN → Register(upgrade_token saved to user) → VerifyEmail
                                                                      ↓
                                                     Email link: /verify-email?uid=&exp=&sig=
                                                                      ↓
                                                     ✅ fetchUser → sees pending_upgrade_token
                                                                      ↓
                                                     Redirect to /checkout/{pending_upgrade_token}
                                                                      ↓
                                                     Checkout → Stripe → Webhook links device
```

---

### Task 1: Add `pending_upgrade_token` column to users table

**Files:**
- Create: `config/Migrations/YYYYMMDD000001_AddPendingUpgradeTokenToUsers.php`

- [ ] **Step 1: Create migration**

```php
<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPendingUpgradeTokenToUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('pending_upgrade_token', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
            'after' => 'stripe_customer_id',
        ])
        ->update();
    }
}
```

- [ ] **Step 2: Run migration**

Run: `bin/cake migrations migrate`
Expected: Migration applies successfully, `pending_upgrade_token` column exists on `users` table.

- [ ] **Step 3: Commit**

```bash
git add config/Migrations/*AddPendingUpgradeTokenToUsers.php
git commit -m "feat: add pending_upgrade_token column to users table"
```

---

### Task 2: Backend — Save upgrade_token during registration

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php:78-159` (register method)
- Test: `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php`

- [ ] **Step 1: Write failing test — registration with upgrade_token saves it on user**

Add to the existing `AuthControllerTest`:

```php
public function testRegisterWithUpgradeTokenSavesPendingToken(): void
{
    // Create an upgrade token in DB
    $tokensTable = $this->fetchTable('UpgradeTokens');
    $token = $tokensTable->newEmptyEntity();
    $token->id = \Cake\Utility\Text::uuid();
    $token->token_string = 'TEST-UPGRADE-TOKEN-123';
    $token->device_uuid = 'device-uuid-abc';
    $token->type = 'upgrade';
    $token->is_used = false;
    $token->expires_at = \Cake\I18n\DateTime::now()->addDays(1);
    $tokensTable->save($token);

    $this->post('/api/v1/web/auth/register', [
        'email' => 'upgradetest@example.com',
        'password' => 'Password123!',
        'first_name' => 'Test',
        'last_name' => 'User',
        'upgrade_token' => 'TEST-UPGRADE-TOKEN-123',
    ]);

    $this->assertResponseOk();
    $body = json_decode((string)$this->_response->getBody(), true);
    $this->assertTrue($body['success']);

    // Verify the pending_upgrade_token was saved on the user
    $usersTable = $this->fetchTable('Users');
    $user = $usersTable->find()->where(['email' => 'upgradetest@example.com'])->first();
    $this->assertEquals('TEST-UPGRADE-TOKEN-123', $user->pending_upgrade_token);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter testRegisterWithUpgradeTokenSavesPendingToken`
Expected: FAIL — `pending_upgrade_token` is null because we don't save it yet.

- [ ] **Step 3: Implement — save upgrade_token during registration**

In `src/Controller/Api/V1/Web/AuthController.php`, in the `register()` method, after line 108 (`$user->marketing_optin = ...`), add:

```php
// Persist upgrade token for the checkout flow after email verification
$upgradeToken = $this->request->getData('upgrade_token');
if ($upgradeToken) {
    $tokensTable = $this->fetchTable('UpgradeTokens');
    $validToken = $tokensTable->find()
        ->where(['token_string' => $upgradeToken, 'type' => 'upgrade', 'is_used' => false])
        ->first();
    if ($validToken) {
        $user->pending_upgrade_token = $upgradeToken;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter testRegisterWithUpgradeTokenSavesPendingToken`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
git commit -m "feat: save pending_upgrade_token on user during registration"
```

---

### Task 3: Backend — Use pending_upgrade_token in createCheckout fallback

**Files:**
- Modify: `src/Controller/Api/V1/Web/SubscriptionsController.php:29-139` (createCheckout method)
- Test: `tests/TestCase/Controller/Api/V1/Web/SubscriptionsControllerTest.php`

- [ ] **Step 1: Write failing test — createCheckout uses pending_upgrade_token when no explicit token**

```php
public function testCreateCheckoutUsesPendingUpgradeToken(): void
{
    // Create user with pending_upgrade_token
    $usersTable = $this->fetchTable('Users');
    $user = $usersTable->get($this->userId); // existing test user
    $user->pending_upgrade_token = 'PENDING-TOKEN-XYZ';
    $usersTable->save($user);

    // Create matching upgrade token
    $tokensTable = $this->fetchTable('UpgradeTokens');
    $token = $tokensTable->newEmptyEntity();
    $token->id = \Cake\Utility\Text::uuid();
    $token->token_string = 'PENDING-TOKEN-XYZ';
    $token->device_uuid = 'pending-device-uuid';
    $token->type = 'upgrade';
    $token->is_used = false;
    $token->expires_at = \Cake\I18n\DateTime::now()->addDays(1);
    $tokensTable->save($token);

    // Call createCheckout WITHOUT passing upgrade_token in body
    $this->post('/api/v1/web/subscriptions/checkout', []);

    $this->assertResponseOk();
    // The Stripe session should have been created with device_uuid metadata
    // (exact assertion depends on Stripe mocking setup)
}
```

Note: If Stripe is not mocked in tests, this test may need to verify the logic differently (e.g., check that the pending token is cleared, or mock Stripe). Adapt to the existing test patterns in `SubscriptionsControllerTest.php`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter testCreateCheckoutUsesPendingUpgradeToken`
Expected: FAIL

- [ ] **Step 3: Implement — fallback to pending_upgrade_token**

In `src/Controller/Api/V1/Web/SubscriptionsController.php`, modify the upgrade token lookup section (around line 79). Replace:

```php
$upgradeTokenString = $this->request->getData('upgrade_token');
```

With:

```php
$upgradeTokenString = $this->request->getData('upgrade_token');

// Fallback to user's pending upgrade token from registration flow
if (!$upgradeTokenString) {
    $upgradeTokenString = $user->pending_upgrade_token;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter testCreateCheckoutUsesPendingUpgradeToken`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Controller/Api/V1/Web/SubscriptionsController.php tests/TestCase/Controller/Api/V1/Web/SubscriptionsControllerTest.php
git commit -m "feat: fallback to pending_upgrade_token in createCheckout"
```

---

### Task 4: Backend — Clear pending_upgrade_token after device linking

**Files:**
- Modify: `src/Controller/Api/V1/WebhooksController.php:162-186` (device linking section)
- Modify: `src/Controller/Api/V1/Web/DevicesController.php:143-192` (linkUpgradeToken method)

- [ ] **Step 1: Add cleanup in WebhooksController after device linking**

In `src/Controller/Api/V1/WebhooksController.php`, after the device is saved and token marked as used (around line 185), add:

```php
// Clear user's pending upgrade token
$user->pending_upgrade_token = null;
$usersTable = $this->fetchTable('Users');
$usersTable->save($user);
```

Note: `$user` is already available in scope from the webhook handler.

- [ ] **Step 2: Add cleanup in DevicesController::linkUpgradeToken**

In `src/Controller/Api/V1/Web/DevicesController.php`, after the token is marked as used (line 188), add:

```php
// Clear user's pending upgrade token
$usersTable = $this->fetchTable('Users');
$userEntity = $usersTable->get($userId);
$userEntity->pending_upgrade_token = null;
$usersTable->save($userEntity);
```

- [ ] **Step 3: Run existing tests**

Run: `composer test`
Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add src/Controller/Api/V1/WebhooksController.php src/Controller/Api/V1/Web/DevicesController.php
git commit -m "feat: clear pending_upgrade_token after device linking"
```

---

### Task 5: Frontend — Pass upgrade_token to register API

**Files:**
- Modify: `client/src/api/auth.js` (register function)
- Modify: `client/src/stores/auth.js` (register action)
- Modify: `client/src/views/public/RegisterView.vue` (pass upgrade_token)

- [ ] **Step 1: Check auth API register function signature**

Read `client/src/api/auth.js` to see current register function.

- [ ] **Step 2: Add upgrade_token parameter to auth store register**

In `client/src/stores/auth.js`, modify the `register` function to accept and pass `upgradeToken`:

```js
async function register(email, password, firstName, lastName, language, marketingOptin = false, upgradeToken = null) {
    try {
        const response = await authApi.register(email, password, firstName, lastName, language, marketingOptin, upgradeToken)
```

- [ ] **Step 3: Add upgrade_token to authApi.register call**

In `client/src/api/auth.js`, modify the register function to include `upgrade_token` in the payload when present:

```js
register(email, password, firstName, lastName, language, marketingOptin = false, upgradeToken = null) {
    const payload = { email, password, first_name: firstName, last_name: lastName, language, marketing_optin: marketingOptin }
    if (upgradeToken) {
        payload.upgrade_token = upgradeToken
    }
    return api.post('/web/auth/register', payload)
}
```

- [ ] **Step 4: Pass upgrade_token from RegisterView**

In `client/src/views/public/RegisterView.vue`, modify the `handleRegister` call (line 185):

```js
const upgradeToken = route.query.upgrade_token
const result = await authStore.register(email.value, password.value, firstName.value, lastName.value, language.value, marketingOptin.value, upgradeToken || null)
```

- [ ] **Step 5: Commit**

```bash
git add client/src/api/auth.js client/src/stores/auth.js client/src/views/public/RegisterView.vue
git commit -m "feat: pass upgrade_token to backend during registration"
```

---

### Task 6: Frontend — Redirect to checkout after email verification

**Files:**
- Modify: `client/src/views/public/VerifyEmailView.vue:133-151` (onMounted verification handler)

This is the critical fix. After email verification succeeds and `fetchUser()` returns the user with `pending_upgrade_token`, redirect to the checkout landing instead of the dashboard.

- [ ] **Step 1: Modify VerifyEmailView onMounted handler**

In `client/src/views/public/VerifyEmailView.vue`, replace the verification success block (lines 139-144):

```js
// BEFORE:
if (response.success) {
    trackEvent('email_verified')
    await authStore.fetchUser()
    router.replace('/dashboard?verified=1')
}

// AFTER:
if (response.success) {
    trackEvent('email_verified')
    await authStore.fetchUser()

    // Resume checkout flow if user registered from device unlock
    const pendingToken = authStore.user?.pending_upgrade_token
    if (pendingToken) {
        router.replace(`/checkout/${pendingToken}`)
    } else {
        router.replace('/dashboard?verified=1')
    }
}
```

- [ ] **Step 2: Verify manually**

1. Start Stripe CLI: `stripe listen --forward-to https://triggertime.ddev.site/api/v1/webhooks/stripe`
2. Update `STRIPE_WEBHOOK_SECRET` in `config/.env` with the CLI's signing secret
3. Open `/checkout/TOKEN` (generate from app)
4. Create account → verify email → should redirect to `/checkout/TOKEN` → complete purchase → device linked

- [ ] **Step 3: Commit**

```bash
git add client/src/views/public/VerifyEmailView.vue
git commit -m "feat: redirect to checkout after email verification when pending upgrade token exists"
```

---

### Task 7: Fix VerifyEmailView subscribe CTA API call

**Files:**
- Modify: `client/src/views/public/VerifyEmailView.vue:116-126` (handleSubscribe)

There's a bug in the existing `handleSubscribe` — it passes the upgrade token as the raw payload instead of wrapping it in an object.

- [ ] **Step 1: Fix the createCheckout call**

In `client/src/views/public/VerifyEmailView.vue`, replace:

```js
const response = await subscriptionsApi.createCheckout(upgradeToken || null)
```

With:

```js
const payload = upgradeToken ? { upgrade_token: upgradeToken } : {}
const response = await subscriptionsApi.createCheckout(payload)
```

- [ ] **Step 2: Commit**

```bash
git add client/src/views/public/VerifyEmailView.vue
git commit -m "fix: pass upgrade_token as object to createCheckout API"
```

---

## Summary of Changes

| File | Change |
|------|--------|
| `config/Migrations/*_AddPendingUpgradeTokenToUsers.php` | New column |
| `src/Controller/Api/V1/Web/AuthController.php` | Save `pending_upgrade_token` on register |
| `src/Controller/Api/V1/Web/SubscriptionsController.php` | Fallback to `pending_upgrade_token` |
| `src/Controller/Api/V1/WebhooksController.php` | Clear token after device link |
| `src/Controller/Api/V1/Web/DevicesController.php` | Clear token after device link |
| `client/src/api/auth.js` | Add `upgradeToken` param to register |
| `client/src/stores/auth.js` | Pass `upgradeToken` through register |
| `client/src/views/public/RegisterView.vue` | Pass `upgrade_token` from query |
| `client/src/views/public/VerifyEmailView.vue` | Redirect to checkout after verification + fix API call |
