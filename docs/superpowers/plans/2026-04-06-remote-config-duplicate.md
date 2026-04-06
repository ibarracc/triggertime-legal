# Remote Config Duplicate Feature — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Duplicate" action to the remote config detail view that copies a config's data to a new instance/version combination via a dedicated backend endpoint.

**Architecture:** New `POST /admin/remote-config/:id/duplicate` endpoint on the backend. Frontend adds a button + modal to the detail view that calls the new endpoint and navigates to the new config on success.

**Tech Stack:** CakePHP 5.3 (PHP 8.2+), Vue 3 Composition API, Axios

---

### Task 1: Add the `duplicate` route

**Files:**
- Modify: `config/routes.php:108`

- [ ] **Step 1: Add the custom route**

In `config/routes.php`, after `$admin->resources('RemoteConfig');` (line 108), add the duplicate route:

```php
$admin->post('/remote-config/{id}/duplicate', ['controller' => 'RemoteConfig', 'action' => 'duplicate'])->setPass(['id']);
```

- [ ] **Step 2: Commit**

```bash
git add config/routes.php
git commit -m "feat: add route for remote config duplicate endpoint"
```

---

### Task 2: Implement the `duplicate` controller action

**Files:**
- Modify: `src/Controller/Api/V1/Admin/RemoteConfigController.php`

- [ ] **Step 1: Write the `duplicate` method**

Add the following method to `RemoteConfigController` after the `delete` method:

```php
/**
 * Duplicate a remote config to a new instance/version combination.
 *
 * @param string $id Source config record ID.
 */
public function duplicate(string $id)
{
    $this->request->allowMethod(['post']);
    $table = $this->fetchTable('RemoteConfig');

    // Load source config (throws RecordNotFoundException → 404)
    $source = $table->get($id);

    $data = $this->request->getData();

    // Validate instance_id is present
    if (empty($data['instance_id'])) {
        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => 'instance_id is required',
            ]));
    }

    // Validate instance exists
    $instancesTable = $this->fetchTable('Instances');
    $instance = $instancesTable->find()->where(['id' => $data['instance_id']])->first();
    if (!$instance) {
        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => 'Instance not found',
            ]));
    }

    // Validate version belongs to selected instance (if provided)
    $versionId = $data['version_id'] ?? null;
    if ($versionId !== null) {
        $versionsTable = $this->fetchTable('Versions');
        $version = $versionsTable->find()
            ->where(['id' => $versionId, 'instance_id' => $data['instance_id']])
            ->first();
        if (!$version) {
            return $this->response->withType('application/json')->withStatus(422)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Version does not belong to the selected instance',
                ]));
        }
    }

    // Check uniqueness (instance_id + version_id pair)
    $existingConditions = [
        'instance_id' => $data['instance_id'],
        'version_id IS' => $versionId,
    ];
    $existing = $table->find()->where($existingConditions)->first();
    if ($existing) {
        return $this->response->withType('application/json')->withStatus(422)
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => 'A config already exists for this instance/version combination',
            ]));
    }

    // Create the duplicate
    $newConfig = $table->newEntity([
        'instance_id' => $data['instance_id'],
        'version_id' => $versionId,
        'config_data' => $source->config_data,
        'app_instance' => $instance->name,
    ]);

    if ($table->save($newConfig)) {
        $newConfig = $table->get($newConfig->id, contain: ['Instances', 'Versions']);

        return $this->response->withType('application/json')->withStatus(201)
            ->withStringBody((string)json_encode(['success' => true, 'config' => $newConfig]));
    }

    return $this->response->withType('application/json')->withStatus(422)
        ->withStringBody((string)json_encode(['success' => false, 'errors' => $newConfig->getErrors()]));
}
```

- [ ] **Step 2: Verify coding standards**

Run:
```bash
vendor/bin/phpcs src/Controller/Api/V1/Admin/RemoteConfigController.php
```

Expected: No errors.

- [ ] **Step 3: Commit**

```bash
git add src/Controller/Api/V1/Admin/RemoteConfigController.php
git commit -m "feat: implement duplicate action in RemoteConfigController"
```

---

### Task 3: Write backend tests for the duplicate endpoint

**Files:**
- Create: `tests/TestCase/Controller/Api/V1/Admin/RemoteConfigControllerTest.php`
- Modify: `tests/Fixture/AppRemoteConfigFixture.php`
- Modify: `tests/Fixture/VersionsFixture.php`

- [ ] **Step 1: Add fixture data**

Update `tests/Fixture/VersionsFixture.php` to have records with instance_id references:

```php
public function init(): void
{
    $this->records = [
        [
            'id' => 1,
            'version' => '1.0.0',
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'created' => 1771994278,
            'modified' => 1771994278,
        ],
        [
            'id' => 2,
            'version' => '2.0.0',
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'created' => 1771994278,
            'modified' => 1771994278,
        ],
    ];
    parent::init();
}
```

Update `tests/Fixture/AppRemoteConfigFixture.php` to have a source record:

```php
public function init(): void
{
    $this->records = [
        [
            'id' => 1,
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 1,
            'config_data' => '{"feature_enabled":true,"max_retries":3}',
            'app_instance' => 'Lorem ipsum dolor sit amet',
            'created' => '2026-03-01 00:00:00',
            'modified' => '2026-03-01 00:00:00',
        ],
    ];
    parent::init();
}
```

- [ ] **Step 2: Create the test file**

Create `tests/TestCase/Controller/Api/V1/Admin/RemoteConfigControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Service\JwtService;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class RemoteConfigControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Instances',
        'app.Versions',
        'app.AppRemoteConfig',
    ];

    /**
     * Generate an admin JWT for authenticated requests.
     */
    private function getAdminToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => 'test-admin-id',
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
    }

    /**
     * Helper to configure an authenticated admin JSON request.
     */
    private function configureAdminRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAdminToken(),
            ],
        ]);
    }

    public function testDuplicateSuccess(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 2,
        ]));
        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('279fd979-5501-4ea2-9137-0160f3770c85', $body['config']['instance_id']);
        $this->assertEquals(2, $body['config']['version_id']);
        // Verify config_data was copied from source
        $configData = $body['config']['config_data'];
        $parsed = is_string($configData) ? json_decode($configData, true) : $configData;
        $this->assertTrue($parsed['feature_enabled']);
        $this->assertEquals(3, $parsed['max_retries']);
    }

    public function testDuplicateSourceNotFound(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/9999/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
        ]));
        $this->assertResponseCode(404);
    }

    public function testDuplicateMissingInstanceId(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([]));
        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('instance_id', $body['message']);
    }

    public function testDuplicateExistingPairReturns422(): void
    {
        $this->configureAdminRequest();
        // The fixture already has instance_id + version_id=1 pair
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 1,
        ]));
        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertStringContainsString('already exists', $body['message']);
    }

    public function testDuplicateVersionNotBelongingToInstance(): void
    {
        $this->configureAdminRequest();
        // version_id=2 belongs to the fixture instance, but use a non-existent instance
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '00000000-0000-0000-0000-000000000000',
            'version_id' => 2,
        ]));
        $this->assertResponseCode(422);
    }

    public function testDuplicateToSameInstanceDifferentVersion(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => 2,
        ]));
        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertEquals(2, $body['config']['version_id']);
    }

    public function testDuplicateToGlobalVersion(): void
    {
        $this->configureAdminRequest();
        $this->post('/api/v1/admin/remote-config/1/duplicate', json_encode([
            'instance_id' => '279fd979-5501-4ea2-9137-0160f3770c85',
            'version_id' => null,
        ]));
        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertNull($body['config']['version_id']);
    }
}
```

- [ ] **Step 3: Run the tests**

Run:
```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Admin/RemoteConfigControllerTest.php
```

Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Fixture/AppRemoteConfigFixture.php tests/Fixture/VersionsFixture.php tests/TestCase/Controller/Api/V1/Admin/RemoteConfigControllerTest.php
git commit -m "test: add tests for remote config duplicate endpoint"
```

---

### Task 4: Add the `duplicateRemoteConfig` API method in the frontend

**Files:**
- Modify: `client/src/api/admin.js:41`

- [ ] **Step 1: Add the API method**

In `client/src/api/admin.js`, add after the `deleteRemoteConfig` line (line 41):

```javascript
duplicateRemoteConfig: (id, data) => api.post(`/admin/remote-config/${id}/duplicate`, data),
```

- [ ] **Step 2: Commit**

```bash
git add client/src/api/admin.js
git commit -m "feat: add duplicateRemoteConfig API method"
```

---

### Task 5: Add duplicate button and modal to RemoteConfigDetailView

**Files:**
- Modify: `client/src/views/admin/RemoteConfigDetailView.vue`

- [ ] **Step 1: Add the Duplicate button in the template**

In the template, inside the `<div class="flex gap-2">` block (line 13), add the duplicate button after the Download JSON button (after line 17, before the closing `</div>`):

```html
<AppButton v-if="!isComparing" variant="secondary" size="sm" @click="openDuplicateModal">
   <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
   Duplicate
</AppButton>
```

- [ ] **Step 2: Add the Duplicate modal in the template**

At the end of the template, just before the closing `</div>` of the root element (before `</template>`), add the modal:

```html
<!-- Duplicate Modal -->
<AppModal :isOpen="duplicateModal.isOpen" title="Duplicate Remote Config" @close="closeDuplicateModal">
  <div v-if="duplicateModal.error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-3 rounded-lg mb-4 text-sm">
    {{ duplicateModal.error }}
  </div>
  
  <div class="form-group">
    <label class="form-label">Instance</label>
    <select v-model="duplicateModal.instance_id" class="form-select text-black" @change="onDuplicateInstanceChange">
      <option :value="null">-- Select Instance --</option>
      <option v-for="inst in duplicateInstances" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
    </select>
  </div>

  <div class="form-group mt-4">
    <label class="form-label">Version (Optional)</label>
    <select v-model="duplicateModal.version_id" class="form-select text-black" :disabled="!duplicateModal.instance_id">
      <option :value="null">Global (All versions for this instance)</option>
      <option v-for="ver in duplicateAvailableVersions" :key="ver.id" :value="ver.id">v{{ ver.version }}</option>
    </select>
  </div>

  <div class="flex gap-3 justify-end mt-6">
    <AppButton variant="secondary" @click="closeDuplicateModal">Cancel</AppButton>
    <AppButton @click="submitDuplicate" :loading="duplicateModal.loading">Duplicate</AppButton>
  </div>
</AppModal>
```

- [ ] **Step 3: Add the AppModal import**

In the `<script setup>` block, add `AppModal` to the imports (line 269):

```javascript
import AppModal from '@/components/ui/AppModal.vue'
```

- [ ] **Step 4: Add the duplicate state and logic**

In the `<script setup>` block, after the existing state declarations (after `const addKeyError = ref('')` around line 296), add:

```javascript
// Duplicate modal state
const duplicateModal = ref({
    isOpen: false,
    instance_id: null,
    version_id: null,
    error: '',
    loading: false,
})
const duplicateInstances = ref([])
const duplicateAllVersions = ref([])

const duplicateAvailableVersions = computed(() => {
    if (!duplicateModal.value.instance_id) return []
    return duplicateAllVersions.value.filter(v => v.instance_id === duplicateModal.value.instance_id)
})

const openDuplicateModal = async () => {
    duplicateModal.value = {
        isOpen: true,
        instance_id: config.value.instance_id || null,
        version_id: null,
        error: '',
        loading: false,
    }
    try {
        const [instancesRes, versionsRes] = await Promise.all([
            adminApi.getInstances(),
            adminApi.getVersions(),
        ])
        duplicateInstances.value = instancesRes.instances || []
        duplicateAllVersions.value = versionsRes.versions || []
    } catch (err) {
        duplicateModal.value.error = 'Failed to load instances/versions'
    }
}

const closeDuplicateModal = () => {
    duplicateModal.value.isOpen = false
}

const onDuplicateInstanceChange = () => {
    duplicateModal.value.version_id = null
}

const submitDuplicate = async () => {
    duplicateModal.value.error = ''
    duplicateModal.value.loading = true
    try {
        const response = await adminApi.duplicateRemoteConfig(configId, {
            instance_id: duplicateModal.value.instance_id,
            version_id: duplicateModal.value.version_id,
        })
        closeDuplicateModal()
        router.push({ name: 'admin-remote-config-detail', params: { id: response.config.id } })
    } catch (err) {
        duplicateModal.value.error = err.response?.data?.message || 'Failed to duplicate config'
    } finally {
        duplicateModal.value.loading = false
    }
}
```

- [ ] **Step 5: Verify the page loads**

Run (from `client/`):
```bash
npm run build
```

Expected: Build succeeds without errors.

- [ ] **Step 6: Commit**

```bash
git add client/src/views/admin/RemoteConfigDetailView.vue
git commit -m "feat: add duplicate button and modal to remote config detail view"
```

---

### Task 6: Run full test suite and verify

- [ ] **Step 1: Run backend tests**

```bash
composer check
```

Expected: All tests pass, no coding standard violations.

- [ ] **Step 2: Run frontend build**

```bash
cd client && npm run build
```

Expected: Build succeeds.

- [ ] **Step 3: Final commit (if any fixes needed)**

If any fixes were required, commit them with an appropriate message.
