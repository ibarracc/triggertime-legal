# Marketing Opt-in & Account Deletion Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add GDPR-compliant marketing opt-in to registration, marketing toggle + account deletion to profile, and a purge command for hard-deleting deactivated users after 30 days.

**Architecture:** New `marketing_optin` boolean column on users table. Backend AuthController gets updated register/socialLogin/updateProfile methods plus a new deleteAccount endpoint. Frontend RegisterView gets an opt-in checkbox, ProfileView gets a toggle and delete section. PurgeDeletedUsersCommand handles scheduled hard-deletes.

**Tech Stack:** CakePHP 5.3 (PHP 8.2+), Vue 3 Composition API, Pinia, vue-i18n, Vite

---

### Task 1: Database Migration — Add marketing_optin to users

**Files:**
- Create: `config/Migrations/YYYYMMDDHHMMSS_AddMarketingOptinToUsers.php`

**Step 1: Create the migration**

```bash
bin/cake bake migration AddMarketingOptinToUsers
```

**Step 2: Edit the migration**

Replace the generated `change()` method body with:

```php
public function change(): void
{
    $table = $this->table('users');
    $table->addColumn('marketing_optin', 'boolean', [
        'default' => false,
        'null' => false,
        'after' => 'language',
    ])->update();
}
```

**Step 3: Run the migration**

```bash
bin/cake migrations migrate
```

Expected: Migration runs successfully, `users` table now has `marketing_optin` column.

**Step 4: Commit**

```bash
git add config/Migrations/*AddMarketingOptinToUsers*
git commit -m "feat: add marketing_optin column to users table"
```

---

### Task 2: Backend — Update User entity and UsersTable

**Files:**
- Modify: `src/Model/Entity/User.php:10-23`
- Modify: `src/Model/Table/UsersTable.php:48-63`
- Modify: `tests/Fixture/UsersFixture.php:20-31`

**Step 1: Add marketing_optin to User entity accessible fields**

In `src/Model/Entity/User.php`, add `'marketing_optin' => true` to the `$_accessible` array:

```php
protected array $_accessible = [
    'first_name' => true,
    'last_name' => true,
    'email' => true,
    'password_hash' => true,
    'role' => true,
    'stripe_customer_id' => true,
    'language' => true,
    'marketing_optin' => true,
    'created_at' => true,
    'devices' => true,
    'subscriptions' => true,
    'activation_licenses' => true,
    'social_accounts' => true,
];
```

**Step 2: Add validation rule to UsersTable**

In `src/Model/Table/UsersTable.php`, add after the `language` validation block:

```php
$validator
    ->boolean('marketing_optin')
    ->allowEmptyString('marketing_optin');
```

**Step 3: Update fixture**

In `tests/Fixture/UsersFixture.php`, add `marketing_optin` to the admin fixture record:

```php
$this->records = [
    [
        'id' => 'c3792a3c-af61-479e-aaa3-16e763aacbf8',
        'email' => 'admin@example.com',
        'password_hash' => '$2y$10$72vI/zC71e8D7DrmXOTN6em/6W8k7cOoI6n3u2T4C2Tpw/s4kO53O',
        'role' => 'admin',
        'stripe_customer_id' => null,
        'marketing_optin' => false,
        'created' => '2026-01-01 00:00:00',
        'modified' => '2026-01-01 00:00:00',
    ],
];
```

**Step 4: Run tests to verify nothing breaks**

```bash
composer check
```

Expected: All existing tests pass.

**Step 5: Commit**

```bash
git add src/Model/Entity/User.php src/Model/Table/UsersTable.php tests/Fixture/UsersFixture.php
git commit -m "feat: add marketing_optin to User entity, validation, and fixture"
```

---

### Task 3: Backend — Update register() and socialLogin() to accept marketing_optin

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php:77-144` (register)
- Modify: `src/Controller/Api/V1/Web/AuthController.php:280-389` (socialLogin)

**Step 1: Update register()**

In `AuthController.php`, inside `register()`, after `$language = ...` (line 85), add:

```php
$marketingOptin = (bool)$this->request->getData('marketing_optin', false);
```

Then after `$user->language = $language;` (line 105), add:

```php
$user->marketing_optin = $marketingOptin;
```

**Step 2: Update socialLogin()**

In `AuthController.php`, inside `socialLogin()`, in the "Create new user" block (around line 334), after `$user->language = ...` (line 340), add:

```php
$user->marketing_optin = (bool)$this->request->getData('marketing_optin', false);
```

**Step 3: Run tests**

```bash
composer check
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php
git commit -m "feat: accept marketing_optin in register and socialLogin endpoints"
```

---

### Task 4: Backend — Update updateProfile() and me() for marketing_optin

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php:149-183` (me)
- Modify: `src/Controller/Api/V1/Web/AuthController.php:394-413` (updateProfile)

**Step 1: Update updateProfile()**

In `updateProfile()`, after `$user->language = ...` (line 402), add:

```php
if ($this->request->getData('marketing_optin') !== null) {
    $user->marketing_optin = (bool)$this->request->getData('marketing_optin');
}
```

**Step 2: Update me() to include subscription eligibility**

In `me()`, before the return statement (line 175), add logic to determine if delete is allowed:

```php
// Determine if user can delete account (no active paid subscription)
$activeSubscription = null;
$canDeleteAccount = true;
if ($user->subscriptions) {
    foreach ($user->subscriptions as $sub) {
        if ($sub->status === 'active' && $sub->plan !== 'free') {
            if (!$sub->cancel_at_period_end || $sub->current_period_end > DateTime::now()) {
                $canDeleteAccount = false;
                $activeSubscription = $sub;
                break;
            }
        }
    }
}
```

Then update the return JSON to include `can_delete_account`:

```php
return $this->response->withType('application/json')
    ->withStringBody((string)json_encode([
        'success' => true,
        'user' => $user,
        'has_password' => !empty($user->password_hash),
        'can_delete_account' => $canDeleteAccount,
        'b2b_licenses' => $licenses,
        'instances' => $instances,
    ]));
```

**Step 3: Run tests**

```bash
composer check
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php
git commit -m "feat: add marketing_optin to updateProfile, add can_delete_account to me()"
```

---

### Task 5: Backend — Add deleteAccount() endpoint and route

**Files:**
- Modify: `src/Controller/Api/V1/Web/AuthController.php` (add new method at end)
- Modify: `config/routes.php:85-89`

**Step 1: Add deleteAccount() method**

In `AuthController.php`, add this method before the closing `}` of the class:

```php
/**
 * Soft-delete the authenticated user's account.
 *
 * Requires email confirmation and no active paid subscription.
 */
public function deleteAccount()
{
    $this->request->allowMethod(['delete']);
    $payload = $this->request->getAttribute('jwt_payload');

    $email = $this->request->getData('email');
    if (!$email) {
        throw new BadRequestException('Email confirmation is required');
    }

    $user = $this->Authentication->find()
        ->where(['id' => $payload['sub']])
        ->contain(['Subscriptions'])
        ->first();

    if (!$user) {
        throw new UnauthorizedException('User not found');
    }

    // Verify email matches
    if (strtolower($email) !== strtolower($user->email)) {
        throw new BadRequestException('Email does not match your account');
    }

    // Check for active paid subscription
    if ($user->subscriptions) {
        foreach ($user->subscriptions as $sub) {
            if ($sub->status === 'active' && $sub->plan !== 'free') {
                if (!$sub->cancel_at_period_end || $sub->current_period_end > DateTime::now()) {
                    throw new BadRequestException(
                        'Cannot delete account with an active paid subscription. '
                        . 'Please cancel your subscription and wait for it to expire.'
                    );
                }
            }
        }
    }

    // Soft-delete user
    if (!$this->Authentication->delete($user)) {
        throw new BadRequestException('Failed to delete account');
    }

    return $this->response->withType('application/json')
        ->withStringBody((string)json_encode([
            'success' => true,
            'message' => 'Your account has been deactivated and will be permanently deleted in 30 days.',
        ]));
}
```

**Step 2: Add route**

In `config/routes.php`, inside the JWT-authenticated scope (line 79-93), after the `social-disconnect` route (line 89), add:

```php
$webAuth->delete('/me', ['controller' => 'Auth', 'action' => 'deleteAccount']);
```

**Step 3: Run tests**

```bash
composer check
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add src/Controller/Api/V1/Web/AuthController.php config/routes.php
git commit -m "feat: add deleteAccount endpoint with subscription guard"
```

---

### Task 6: Backend — Add PurgeDeletedUsersCommand

**Files:**
- Create: `src/Command/PurgeDeletedUsersCommand.php`

**Step 1: Create the command file**

```php
<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;

class PurgeDeletedUsersCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Permanently delete users that were soft-deleted more than 30 days ago.');
        $parser->addOption('dry-run', [
            'help' => 'Show what would be deleted without actually deleting',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $dryRun = (bool)$args->getOption('dry-run');
        $cutoff = DateTime::now()->subDays(30);

        $usersTable = $this->fetchTable('Users');

        // Bypass SoftDelete to find soft-deleted users
        $users = $usersTable->find()
            ->disableAutoFields()
            ->select(['id', 'email', 'deleted_at'])
            ->where([
                'deleted_at IS NOT' => null,
                'deleted_at <=' => $cutoff,
            ])
            ->disableResultsCasting()
            ->all();

        $count = $users->count();

        if ($count === 0) {
            $io->out('No users to purge.');

            return static::CODE_SUCCESS;
        }

        $io->out(sprintf('Found %d user(s) to purge (deleted before %s).', $count, $cutoff->format('Y-m-d H:i:s')));

        if ($dryRun) {
            foreach ($users as $user) {
                $io->out(sprintf('  [DRY RUN] Would purge user %s (%s)', $user['id'], $user['email']));
            }

            return static::CODE_SUCCESS;
        }

        $connection = $usersTable->getConnection();
        $purged = 0;

        foreach ($users as $user) {
            try {
                $connection->delete('social_accounts', ['user_id' => $user['id']]);
                $connection->delete('devices', ['user_id' => $user['id']]);
                $connection->delete('subscriptions', ['user_id' => $user['id']]);
                $connection->delete('activation_licenses', ['user_id' => $user['id']]);
                $connection->delete('users', ['id' => $user['id']]);
                $purged++;
                $io->out(sprintf('  Purged user %s (%s)', $user['id'], $user['email']));
                Log::info(sprintf('Purged deleted user %s (%s)', $user['id'], $user['email']));
            } catch (\Exception $e) {
                $io->err(sprintf('  Failed to purge user %s: %s', $user['id'], $e->getMessage()));
                Log::error(sprintf('Failed to purge user %s: %s', $user['id'], $e->getMessage()));
            }
        }

        $io->out(sprintf('Purged %d/%d users.', $purged, $count));

        return static::CODE_SUCCESS;
    }
}
```

**Step 2: Verify command is discoverable**

```bash
bin/cake purge_deleted_users --dry-run
```

Expected: "No users to purge." (since no soft-deleted users exist yet).

**Step 3: Run tests**

```bash
composer check
```

Expected: All tests pass. No code style issues.

**Step 4: Commit**

```bash
git add src/Command/PurgeDeletedUsersCommand.php
git commit -m "feat: add PurgeDeletedUsersCommand for 30-day hard-delete of deactivated users"
```

---

### Task 7: Backend — Write tests for new endpoints

**Files:**
- Modify: `tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php`

**Step 1: Add test for register with marketing_optin**

```php
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
```

**Step 2: Add test for deleteAccount requires email**

```php
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
    ]);
    $this->delete('/api/v1/web/me', json_encode([]));
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
    ]);
    $this->delete('/api/v1/web/me', json_encode(['email' => 'wrong@example.com']));
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
    ]);
    $this->delete('/api/v1/web/me', json_encode(['email' => 'delete-test3@example.com']));
    $this->assertResponseOk();
    $deleteBody = json_decode((string)$this->_response->getBody(), true);
    $this->assertTrue($deleteBody['success']);
}
```

**Step 3: Run tests**

```bash
composer test
```

Expected: All new tests pass.

**Step 4: Run full check**

```bash
composer check
```

Expected: Tests + code style pass.

**Step 5: Commit**

```bash
git add tests/TestCase/Controller/Api/V1/Web/AuthControllerTest.php
git commit -m "test: add tests for marketing_optin and deleteAccount endpoints"
```

---

### Task 8: Frontend — Update auth.js API module

**Files:**
- Modify: `client/src/api/auth.js`

**Step 1: Update register() to accept marketingOptin**

Change the `register` method from:

```javascript
register(email, password, firstName, lastName, language) {
    return api.post('/web/auth/register', { email, password, first_name: firstName, last_name: lastName, language });
},
```

To:

```javascript
register(email, password, firstName, lastName, language, marketingOptin = false) {
    return api.post('/web/auth/register', { email, password, first_name: firstName, last_name: lastName, language, marketing_optin: marketingOptin });
},
```

**Step 2: Update socialLogin() to accept marketingOptin**

Change the `socialLogin` method from:

```javascript
socialLogin(provider, idToken, firstName, lastName) {
    return api.post('/web/auth/social-login', {
        provider,
        id_token: idToken,
        first_name: firstName,
        last_name: lastName,
    });
},
```

To:

```javascript
socialLogin(provider, idToken, firstName, lastName, marketingOptin = false) {
    return api.post('/web/auth/social-login', {
        provider,
        id_token: idToken,
        first_name: firstName,
        last_name: lastName,
        marketing_optin: marketingOptin,
    });
},
```

**Step 3: Add deleteAccount()**

After the `disconnectSocial` method, add:

```javascript
deleteAccount(email) {
    return api.delete('/web/me', { data: { email } });
}
```

**Step 4: Commit**

```bash
git add client/src/api/auth.js
git commit -m "feat: update auth API — add marketing_optin params and deleteAccount method"
```

---

### Task 9: Frontend — Update auth store

**Files:**
- Modify: `client/src/stores/auth.js:30-52`

**Step 1: Update register() to pass marketingOptin**

Change from:

```javascript
async function register(email, password, firstName, lastName, language) {
    try {
        const response = await authApi.register(email, password, firstName, lastName, language)
```

To:

```javascript
async function register(email, password, firstName, lastName, language, marketingOptin = false) {
    try {
        const response = await authApi.register(email, password, firstName, lastName, language, marketingOptin)
```

**Step 2: Update socialLogin() to pass marketingOptin**

Change from:

```javascript
async function socialLogin(provider, idToken, firstName, lastName) {
    try {
        const response = await authApi.socialLogin(provider, idToken, firstName, lastName)
```

To:

```javascript
async function socialLogin(provider, idToken, firstName, lastName, marketingOptin = false) {
    try {
        const response = await authApi.socialLogin(provider, idToken, firstName, lastName, marketingOptin)
```

**Step 3: Commit**

```bash
git add client/src/stores/auth.js
git commit -m "feat: pass marketing_optin through auth store register/socialLogin"
```

---

### Task 10: Frontend — Update useSocialAuth composable

**Files:**
- Modify: `client/src/composables/useSocialAuth.js:92-134`

**Step 1: Add marketingOptin parameter to loginWithGoogle**

Change from:

```javascript
async function loginWithGoogle() {
    isLoading.value = true
    error.value = ''

    try {
        const idToken = await getGoogleIdToken()

        const result = await authStore.socialLogin('google', idToken)
```

To:

```javascript
async function loginWithGoogle(marketingOptin = false) {
    isLoading.value = true
    error.value = ''

    try {
        const idToken = await getGoogleIdToken()

        const result = await authStore.socialLogin('google', idToken, null, null, marketingOptin)
```

**Step 2: Add marketingOptin parameter to loginWithApple**

Change from:

```javascript
async function loginWithApple() {
    isLoading.value = true
    error.value = ''

    try {
        const { idToken, firstName, lastName } = await getAppleIdToken()

        const result = await authStore.socialLogin('apple', idToken, firstName, lastName)
```

To:

```javascript
async function loginWithApple(marketingOptin = false) {
    isLoading.value = true
    error.value = ''

    try {
        const { idToken, firstName, lastName } = await getAppleIdToken()

        const result = await authStore.socialLogin('apple', idToken, firstName, lastName, marketingOptin)
```

**Step 3: Commit**

```bash
git add client/src/composables/useSocialAuth.js
git commit -m "feat: pass marketing_optin through social auth composable"
```

---

### Task 11: Frontend — Add i18n keys to all 8 locale files

**Files:**
- Modify: `client/src/i18n/locales/en.json`
- Modify: `client/src/i18n/locales/es.json`
- Modify: `client/src/i18n/locales/ca.json`
- Modify: `client/src/i18n/locales/de.json`
- Modify: `client/src/i18n/locales/eu.json`
- Modify: `client/src/i18n/locales/fr.json`
- Modify: `client/src/i18n/locales/gl.json`
- Modify: `client/src/i18n/locales/pt.json`

**Step 1: Add keys to en.json**

In the `"auth"` section, after `"social_login_failed"`, add:

```json
"marketing_optin": "I agree to receive marketing communications about product updates, offers, and tips"
```

In the `"profile"` section, after `"b2b_subtitle"`, add:

```json
"communications": "Communications",
"communications_subtitle": "Manage your marketing communication preferences.",
"marketing_optin": "Receive marketing communications",
"marketing_optin_desc": "Get updates about new features, offers, and shooting tips.",
"delete_account": "Delete Account",
"delete_account_desc": "Permanently delete your account and all associated data. This action cannot be undone.",
"delete_account_disabled": "You must cancel your subscription and wait for it to expire before deleting your account.",
"delete_account_confirm": "Confirm Account Deletion",
"delete_account_confirm_text": "Type your email address to confirm. Your account will be deactivated immediately and permanently deleted after 30 days.",
"delete_account_success": "Your account has been deactivated and will be permanently deleted in 30 days.",
"type_email_to_confirm": "Type your email to confirm",
"email_mismatch": "Email does not match your account"
```

In the `"auth"` section, add:

```json
"account_deactivated": "Your account has been deactivated and will be permanently deleted in 30 days."
```

**Step 2: Add keys to es.json**

In the `"auth"` section, after `"social_login_failed"`, add:

```json
"marketing_optin": "Acepto recibir comunicaciones comerciales sobre actualizaciones del producto, ofertas y consejos"
```

In the `"profile"` section, after `"b2b_subtitle"`, add:

```json
"communications": "Comunicaciones",
"communications_subtitle": "Gestiona tus preferencias de comunicaciones comerciales.",
"marketing_optin": "Recibir comunicaciones comerciales",
"marketing_optin_desc": "Recibe novedades sobre nuevas funciones, ofertas y consejos de tiro.",
"delete_account": "Eliminar Cuenta",
"delete_account_desc": "Elimina permanentemente tu cuenta y todos los datos asociados. Esta acción no se puede deshacer.",
"delete_account_disabled": "Debes cancelar tu suscripción y esperar a que expire antes de eliminar tu cuenta.",
"delete_account_confirm": "Confirmar Eliminación de Cuenta",
"delete_account_confirm_text": "Escribe tu dirección de correo electrónico para confirmar. Tu cuenta será desactivada inmediatamente y eliminada permanentemente después de 30 días.",
"delete_account_success": "Tu cuenta ha sido desactivada y será eliminada permanentemente en 30 días.",
"type_email_to_confirm": "Escribe tu email para confirmar",
"email_mismatch": "El correo electrónico no coincide con tu cuenta"
```

In the `"auth"` section, add:

```json
"account_deactivated": "Tu cuenta ha sido desactivada y será eliminada permanentemente en 30 días."
```

**Step 3: Add keys to remaining 6 locale files (ca, de, eu, fr, gl, pt)**

Follow the same pattern as es.json, translating the values to each respective language. The keys are identical across all locale files, only the values differ.

For **ca.json** (Catalan):
- auth.marketing_optin: "Accepto rebre comunicacions comercials sobre actualitzacions del producte, ofertes i consells"
- auth.account_deactivated: "El teu compte ha estat desactivat i s'eliminarà permanentment en 30 dies."
- profile.communications: "Comunicacions"
- profile.communications_subtitle: "Gestiona les teves preferències de comunicacions comercials."
- profile.marketing_optin: "Rebre comunicacions comercials"
- profile.marketing_optin_desc: "Rep novetats sobre noves funcions, ofertes i consells de tir."
- profile.delete_account: "Eliminar Compte"
- profile.delete_account_desc: "Elimina permanentment el teu compte i totes les dades associades. Aquesta acció no es pot desfer."
- profile.delete_account_disabled: "Has de cancel·lar la teva subscripció i esperar que expiri abans d'eliminar el teu compte."
- profile.delete_account_confirm: "Confirmar Eliminació del Compte"
- profile.delete_account_confirm_text: "Escriu la teva adreça de correu electrònic per confirmar. El teu compte serà desactivat immediatament i eliminat permanentment després de 30 dies."
- profile.delete_account_success: "El teu compte ha estat desactivat i s'eliminarà permanentment en 30 dies."
- profile.type_email_to_confirm: "Escriu el teu email per confirmar"
- profile.email_mismatch: "El correu electrònic no coincideix amb el teu compte"

For **de.json** (German):
- auth.marketing_optin: "Ich stimme dem Erhalt von Marketingmitteilungen über Produktaktualisierungen, Angebote und Tipps zu"
- auth.account_deactivated: "Dein Konto wurde deaktiviert und wird in 30 Tagen dauerhaft gelöscht."
- profile.communications: "Mitteilungen"
- profile.communications_subtitle: "Verwalte deine Marketingkommunikationseinstellungen."
- profile.marketing_optin: "Marketingmitteilungen erhalten"
- profile.marketing_optin_desc: "Erhalte Updates zu neuen Funktionen, Angeboten und Schießtipps."
- profile.delete_account: "Konto löschen"
- profile.delete_account_desc: "Lösche dein Konto und alle zugehörigen Daten dauerhaft. Diese Aktion kann nicht rückgängig gemacht werden."
- profile.delete_account_disabled: "Du musst dein Abonnement kündigen und warten, bis es abläuft, bevor du dein Konto löschen kannst."
- profile.delete_account_confirm: "Kontolöschung bestätigen"
- profile.delete_account_confirm_text: "Gib deine E-Mail-Adresse zur Bestätigung ein. Dein Konto wird sofort deaktiviert und nach 30 Tagen dauerhaft gelöscht."
- profile.delete_account_success: "Dein Konto wurde deaktiviert und wird in 30 Tagen dauerhaft gelöscht."
- profile.type_email_to_confirm: "E-Mail zur Bestätigung eingeben"
- profile.email_mismatch: "Die E-Mail-Adresse stimmt nicht mit deinem Konto überein"

For **eu.json** (Basque):
- auth.marketing_optin: "Onartzen dut produktuaren eguneraketen, eskaintzen eta aholkuen inguruko komunikazio komertzialak jasotzea"
- auth.account_deactivated: "Zure kontua desaktibatu egin da eta 30 egunetan betirako ezabatuko da."
- profile.communications: "Komunikazioak"
- profile.communications_subtitle: "Kudeatu zure komunikazio komertzial hobespenak."
- profile.marketing_optin: "Komunikazio komertzialak jaso"
- profile.marketing_optin_desc: "Jaso funtzio berrien, eskaintzen eta tiro-aholkuen eguneraketak."
- profile.delete_account: "Kontua Ezabatu"
- profile.delete_account_desc: "Zure kontua eta erlazionatutako datu guztiak betirako ezabatu. Ekintza hau ezin da desegin."
- profile.delete_account_disabled: "Zure harpidetza bertan behera utzi eta iraungitzeko itxaron behar duzu kontua ezabatu aurretik."
- profile.delete_account_confirm: "Kontuaren Ezabaketa Berretsi"
- profile.delete_account_confirm_text: "Idatzi zure helbide elektronikoa berresteko. Zure kontua berehala desaktibatuko da eta 30 egunen ondoren betirako ezabatuko da."
- profile.delete_account_success: "Zure kontua desaktibatu egin da eta 30 egunetan betirako ezabatuko da."
- profile.type_email_to_confirm: "Idatzi emaila berresteko"
- profile.email_mismatch: "Helbide elektronikoa ez dator bat zure kontuarekin"

For **fr.json** (French):
- auth.marketing_optin: "J'accepte de recevoir des communications marketing sur les mises à jour du produit, les offres et les conseils"
- auth.account_deactivated: "Votre compte a été désactivé et sera définitivement supprimé dans 30 jours."
- profile.communications: "Communications"
- profile.communications_subtitle: "Gérez vos préférences de communications marketing."
- profile.marketing_optin: "Recevoir les communications marketing"
- profile.marketing_optin_desc: "Recevez des nouvelles sur les nouvelles fonctionnalités, les offres et les conseils de tir."
- profile.delete_account: "Supprimer le Compte"
- profile.delete_account_desc: "Supprimez définitivement votre compte et toutes les données associées. Cette action est irréversible."
- profile.delete_account_disabled: "Vous devez annuler votre abonnement et attendre qu'il expire avant de pouvoir supprimer votre compte."
- profile.delete_account_confirm: "Confirmer la Suppression du Compte"
- profile.delete_account_confirm_text: "Saisissez votre adresse e-mail pour confirmer. Votre compte sera désactivé immédiatement et supprimé définitivement après 30 jours."
- profile.delete_account_success: "Votre compte a été désactivé et sera définitivement supprimé dans 30 jours."
- profile.type_email_to_confirm: "Saisissez votre email pour confirmer"
- profile.email_mismatch: "L'adresse e-mail ne correspond pas à votre compte"

For **gl.json** (Galician):
- auth.marketing_optin: "Acepto recibir comunicacións comerciais sobre actualizacións do produto, ofertas e consellos"
- auth.account_deactivated: "A túa conta foi desactivada e será eliminada permanentemente en 30 días."
- profile.communications: "Comunicacións"
- profile.communications_subtitle: "Xestiona as túas preferencias de comunicacións comerciais."
- profile.marketing_optin: "Recibir comunicacións comerciais"
- profile.marketing_optin_desc: "Recibe novidades sobre novas funcións, ofertas e consellos de tiro."
- profile.delete_account: "Eliminar Conta"
- profile.delete_account_desc: "Elimina permanentemente a túa conta e todos os datos asociados. Esta acción non se pode desfacer."
- profile.delete_account_disabled: "Debes cancelar a túa subscrición e agardar a que expire antes de eliminar a túa conta."
- profile.delete_account_confirm: "Confirmar Eliminación da Conta"
- profile.delete_account_confirm_text: "Escribe o teu enderezo de correo electrónico para confirmar. A túa conta será desactivada inmediatamente e eliminada permanentemente despois de 30 días."
- profile.delete_account_success: "A túa conta foi desactivada e será eliminada permanentemente en 30 días."
- profile.type_email_to_confirm: "Escribe o teu email para confirmar"
- profile.email_mismatch: "O correo electrónico non coincide coa túa conta"

For **pt.json** (Portuguese):
- auth.marketing_optin: "Aceito receber comunicações de marketing sobre atualizações do produto, ofertas e dicas"
- auth.account_deactivated: "A tua conta foi desativada e será eliminada permanentemente em 30 dias."
- profile.communications: "Comunicações"
- profile.communications_subtitle: "Gere as tuas preferências de comunicações de marketing."
- profile.marketing_optin: "Receber comunicações de marketing"
- profile.marketing_optin_desc: "Recebe novidades sobre novas funcionalidades, ofertas e dicas de tiro."
- profile.delete_account: "Eliminar Conta"
- profile.delete_account_desc: "Elimina permanentemente a tua conta e todos os dados associados. Esta ação não pode ser desfeita."
- profile.delete_account_disabled: "Deves cancelar a tua subscrição e esperar que expire antes de eliminares a tua conta."
- profile.delete_account_confirm: "Confirmar Eliminação da Conta"
- profile.delete_account_confirm_text: "Escreve o teu endereço de email para confirmar. A tua conta será desativada imediatamente e eliminada permanentemente após 30 dias."
- profile.delete_account_success: "A tua conta foi desativada e será eliminada permanentemente em 30 dias."
- profile.type_email_to_confirm: "Escreve o teu email para confirmar"
- profile.email_mismatch: "O email não corresponde à tua conta"

**Step 4: Commit**

```bash
git add client/src/i18n/locales/
git commit -m "feat: add i18n keys for marketing opt-in, account deletion in all 8 locales"
```

---

### Task 12: Frontend — Update RegisterView.vue

**Files:**
- Modify: `client/src/views/public/RegisterView.vue`

**Step 1: Add marketing opt-in checkbox**

In the template, after the terms-checkbox div (line 54-72) and before the error div (line 74), add:

```html
<div class="terms-checkbox mb-4">
  <label class="flex items-start gap-2 cursor-pointer text-sm text-secondary">
    <input
      v-model="marketingOptin"
      type="checkbox"
      class="terms-input mt-0.5"
    />
    <span>{{ $t('auth.marketing_optin') }}</span>
  </label>
</div>
```

**Step 2: Add ref in script**

After `const termsAccepted = ref(false)` (line 154), add:

```javascript
const marketingOptin = ref(false)
```

**Step 3: Update handleRegister to pass marketingOptin**

Change line 173 from:

```javascript
const result = await authStore.register(email.value, password.value, firstName.value, lastName.value, language.value)
```

To:

```javascript
const result = await authStore.register(email.value, password.value, firstName.value, lastName.value, language.value, marketingOptin.value)
```

**Step 4: Update handleGoogleLogin to pass marketingOptin**

Change line 193 from:

```javascript
const result = await socialAuth.loginWithGoogle()
```

To:

```javascript
const result = await socialAuth.loginWithGoogle(marketingOptin.value)
```

**Step 5: Update handleAppleLogin to pass marketingOptin**

Change line 209 from:

```javascript
const result = await socialAuth.loginWithApple()
```

To:

```javascript
const result = await socialAuth.loginWithApple(marketingOptin.value)
```

**Step 6: Commit**

```bash
git add client/src/views/public/RegisterView.vue
git commit -m "feat: add marketing opt-in checkbox to registration form"
```

---

### Task 13: Frontend — Update ProfileView.vue with marketing toggle and delete account

**Files:**
- Modify: `client/src/views/dashboard/ProfileView.vue`

**Step 1: Add imports**

Add `AppModal` to the imports (after AppBadge):

```javascript
import AppModal from '@/components/ui/AppModal.vue'
```

Add `useRouter` import:

```javascript
import { useRouter } from 'vue-router'
```

**Step 2: Add state variables**

In the script section, after the existing state variables, add:

```javascript
const router = useRouter()

// Marketing opt-in
const marketingOptin = ref(false)
const isUpdatingMarketing = ref(false)
const marketingState = ref({ error: '', success: '' })

// Delete account
const canDeleteAccount = ref(true)
const showDeleteModal = ref(false)
const deleteEmail = ref('')
const isDeleting = ref(false)
const deleteState = ref({ error: '' })
```

**Step 3: Update onMounted to load marketing_optin and can_delete_account**

In `onMounted`, after `hasPassword.value = res.has_password !== false` (line 250), add:

```javascript
marketingOptin.value = res.user?.marketing_optin || false
canDeleteAccount.value = res.can_delete_account !== false
```

**Step 4: Add toggleMarketing function**

After the `connectApple` function, add:

```javascript
const toggleMarketing = async () => {
  marketingState.value.error = ''
  marketingState.value.success = ''
  isUpdatingMarketing.value = true

  try {
    const newValue = !marketingOptin.value
    const res = await authApi.updateProfile({ marketing_optin: newValue })
    if (res.success) {
      marketingOptin.value = newValue
      marketingState.value.success = t('common.success')
    }
  } catch (err) {
    marketingState.value.error = err.response?.data?.message || t('common.error')
  } finally {
    isUpdatingMarketing.value = false
  }
}
```

**Step 5: Add handleDeleteAccount function**

```javascript
const handleDeleteAccount = async () => {
  deleteState.value.error = ''

  if (deleteEmail.value.toLowerCase() !== auth.user?.email?.toLowerCase()) {
    deleteState.value.error = t('profile.email_mismatch')
    return
  }

  isDeleting.value = true

  try {
    const res = await authApi.deleteAccount(deleteEmail.value)
    if (res.success) {
      auth.logout()
      router.push({ path: '/login', query: { deactivated: '1' } })
    }
  } catch (err) {
    deleteState.value.error = err.response?.data?.error?.message || err.response?.data?.message || t('common.error')
  } finally {
    isDeleting.value = false
  }
}
```

**Step 6: Add Communications section to template**

After the B2B Licenses AppCard (line 190), add:

```html
<!-- Communications -->
<AppCard>
  <template #header>{{ $t('profile.communications') }}</template>
  <p class="text-secondary mb-4 text-sm">
    {{ $t('profile.communications_subtitle') }}
  </p>
  <div v-if="marketingState.error" class="alert alert-danger mb-4">
    {{ marketingState.error }}
  </div>
  <div v-if="marketingState.success" class="alert alert-success mb-4">
    {{ marketingState.success }}
  </div>
  <div class="toggle-row">
    <div>
      <div class="font-medium">{{ $t('profile.marketing_optin') }}</div>
      <div class="text-xs text-secondary">{{ $t('profile.marketing_optin_desc') }}</div>
    </div>
    <button
      class="toggle-switch"
      :class="{ active: marketingOptin }"
      :disabled="isUpdatingMarketing"
      @click="toggleMarketing"
      role="switch"
      :aria-checked="marketingOptin"
    >
      <span class="toggle-knob" />
    </button>
  </div>
</AppCard>
```

**Step 7: Add Delete Account section to template**

After the Communications AppCard, add:

```html
<!-- Delete Account -->
<AppCard class="danger-card">
  <template #header>{{ $t('profile.delete_account') }}</template>
  <p class="text-secondary mb-4 text-sm">
    {{ $t('profile.delete_account_desc') }}
  </p>
  <div v-if="!canDeleteAccount" class="alert alert-warning mb-4">
    {{ $t('profile.delete_account_disabled') }}
  </div>
  <AppButton
    variant="danger"
    :disabled="!canDeleteAccount"
    @click="showDeleteModal = true"
  >
    {{ $t('profile.delete_account') }}
  </AppButton>
</AppCard>

<!-- Delete Account Modal -->
<AppModal
  :is-open="showDeleteModal"
  @close="showDeleteModal = false; deleteEmail = ''; deleteState.error = ''"
  :title="$t('profile.delete_account_confirm')"
  size="sm"
>
  <p class="text-secondary text-sm mb-4">
    {{ $t('profile.delete_account_confirm_text') }}
  </p>
  <AppInput
    v-model="deleteEmail"
    :label="$t('common.email')"
    type="email"
    :placeholder="$t('profile.type_email_to_confirm')"
  />
  <div v-if="deleteState.error" class="alert alert-danger mt-4">
    {{ deleteState.error }}
  </div>
  <template #footer>
    <AppButton variant="secondary" @click="showDeleteModal = false">{{ $t('common.cancel') }}</AppButton>
    <AppButton variant="danger" :loading="isDeleting" @click="handleDeleteAccount">{{ $t('common.delete') }}</AppButton>
  </template>
</AppModal>
```

**Step 8: Add styles**

In the `<style scoped>` section, add:

```css
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
}

.toggle-switch {
  position: relative;
  width: 44px;
  height: 24px;
  border-radius: 12px;
  background: var(--border);
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  flex-shrink: 0;
}

.toggle-switch.active {
  background: var(--primary);
}

.toggle-switch:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.toggle-knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  transition: transform 0.2s;
}

.toggle-switch.active .toggle-knob {
  transform: translateX(20px);
}

.danger-card {
  border: 1px solid rgba(255, 77, 77, 0.2);
}

.alert-warning {
  color: #FBBF24;
  font-size: 0.875rem;
  padding: 10px;
  background: rgba(251, 191, 36, 0.1);
  border-radius: 8px;
}
```

**Step 9: Commit**

```bash
git add client/src/views/dashboard/ProfileView.vue
git commit -m "feat: add marketing toggle and delete account section to profile"
```

---

### Task 14: Frontend — Update LoginView.vue with deactivation message

**Files:**
- Modify: `client/src/views/public/LoginView.vue`

**Step 1: Add deactivation banner**

In the template, after the `<h1>` and `<p>` in the text-center div (line 4-7), add:

```html
<div v-if="route.query.deactivated" class="alert alert-info mb-4">
  {{ $t('auth.account_deactivated') }}
</div>
```

**Step 2: Add alert-info style**

In the `<style scoped>` section, add:

```css
.alert-info {
  color: var(--primary);
  font-size: 0.875rem;
  text-align: center;
  padding: 10px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 8px;
}
```

**Step 3: Commit**

```bash
git add client/src/views/public/LoginView.vue
git commit -m "feat: show deactivation message on login page after account deletion"
```

---

### Task 15: Build and verify

**Step 1: Run backend checks**

```bash
composer check
```

Expected: All tests pass, code style OK.

**Step 2: Build frontend**

```bash
cd client && npm run build
```

Expected: Build succeeds without errors.

**Step 3: Commit build output**

```bash
git add webroot/spa/
git commit -m "build: rebuild SPA with marketing opt-in and account deletion features"
```

---

### Task 16: Final review and cleanup

**Step 1: Review all changes**

```bash
git log --oneline feature/sso..HEAD
```

**Step 2: Run full test suite one more time**

```bash
composer check
```

Expected: All green.
