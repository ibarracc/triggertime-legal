<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Service\JwtService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SyncDataControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.SyncWeapons',
        'app.SyncAmmo',
        'app.SyncCompetitions',
        'app.SyncCompetitionReminders',
        'app.SyncAmmoTransactions',
        'app.UserSyncSequences',
    ];

    private string $adminUserId = 'c3792a3c-af61-479e-aaa3-16e763aacbf8';
    private string $regularUserId = 'f2f2f2f2-a3a3-4b4b-8c5c-d6d6d6d6d6d2';

    private function getAdminToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => $this->adminUserId,
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    private function getUserToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => $this->regularUserId,
            'email' => 'user2@example.com',
            'role' => 'user',
        ]);
    }

    private function configureAdminRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAdminToken(),
            ],
        ]);
    }

    private function configureUserRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getUserToken(),
            ],
        ]);
    }

    public function testIndexRequiresAdmin(): void
    {
        $this->configureUserRequest();
        $this->get('/api/v1/admin/sync-data?user_id=' . $this->adminUserId . '&type=weapons');
        $this->assertResponseCode(403);
    }

    public function testIndexRequiresUserIdAndType(): void
    {
        $this->configureAdminRequest();
        $this->get('/api/v1/admin/sync-data');
        $this->assertResponseCode(400);
    }

    public function testIndexInvalidTypeReturns400(): void
    {
        $this->configureAdminRequest();
        $this->get('/api/v1/admin/sync-data?user_id=' . $this->adminUserId . '&type=invalid');
        $this->assertResponseCode(400);
    }

    public function testIndexWeaponsSuccess(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0001',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Test Pistol',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureAdminRequest();
        $this->get('/api/v1/admin/sync-data?user_id=' . $this->adminUserId . '&type=weapons');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['records']);
        $this->assertEquals('Test Pistol', $body['records'][0]['name']);
    }

    public function testEditUpdatesFieldsAndModifiedAt(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0002',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Old Name',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureAdminRequest();
        $this->put('/api/v1/admin/sync-data/aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0002', json_encode([
            'type' => 'weapons',
            'name' => 'Updated Name',
            'caliber' => '.45 ACP',
        ]));
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Updated Name', $body['record']['name']);
        $this->assertEquals('.45 ACP', $body['record']['caliber']);
    }

    public function testEditRejectsNonEditableFields(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0003',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Original',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureAdminRequest();
        $this->put('/api/v1/admin/sync-data/aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0003', json_encode([
            'type' => 'weapons',
            'user_id' => $this->regularUserId,
        ]));
        $this->assertResponseOk();

        $updated = $table->get('aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0003');
        $this->assertEquals($this->adminUserId, $updated->user_id);
    }

    public function testDeleteSoftDeletes(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0004',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'To Delete',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureAdminRequest();
        $this->delete('/api/v1/admin/sync-data/aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0004?type=weapons');
        $this->assertResponseOk();

        $deleted = $table->get('aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0004');
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testDeleteNotFoundReturns404(): void
    {
        $this->configureAdminRequest();
        $this->delete('/api/v1/admin/sync-data/00000000-0000-4000-8000-000000000000?type=weapons');
        $this->assertResponseCode(404);
    }

    public function testEditBumpsVersionAndSeq(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0005',
            'user_id' => $this->adminUserId,
            'device_uuid' => 'test-device',
            'name' => 'Bump Test',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'version' => 1,
            'seq' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureAdminRequest();
        $this->put('/api/v1/admin/sync-data/aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0005', json_encode([
            'type' => 'weapons',
            'name' => 'Bumped Name',
        ]));
        $this->assertResponseOk();

        $updated = $table->get('aaaa1111-bbbb-4ccc-8ddd-eeeeeeee0005');
        $this->assertEquals(2, $updated->version);
        $this->assertGreaterThan(0, $updated->seq);
    }
}
