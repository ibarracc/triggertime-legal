<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Web;

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
        'app.SyncAmmoTransactions',
        'app.UserSyncSequences',
    ];

    private string $userId = 'c3792a3c-af61-479e-aaa3-16e763aacbf8';
    private string $otherUserId = 'f2f2f2f2-a3a3-4b4b-8c5c-d6d6d6d6d6d2';

    private function getUserToken(): string
    {
        $jwt = new JwtService();

        return $jwt->generateToken([
            'sub' => $this->userId,
            'email' => 'admin@example.com',
            'role' => 'user',
        ]);
    }

    private function configureRequest(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getUserToken(),
            ],
        ]);
    }

    public function testEditOwnWeaponSuccess(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'bbbb2222-cccc-4ddd-8eee-ffffff000001',
            'user_id' => $this->userId,
            'device_uuid' => 'test-device',
            'name' => 'My Pistol',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'version' => 1,
            'seq' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureRequest();
        $this->put('/api/v1/web/sync-data/bbbb2222-cccc-4ddd-8eee-ffffff000001', json_encode([
            'type' => 'weapons',
            'name' => 'Updated Pistol',
            'caliber' => '.45 ACP',
        ]));
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Updated Pistol', $body['record']['name']);
        $this->assertEquals('.45 ACP', $body['record']['caliber']);

        $updated = $table->get('bbbb2222-cccc-4ddd-8eee-ffffff000001');
        $this->assertEquals(2, $updated->version);
        $this->assertGreaterThan(0, $updated->seq);
    }

    public function testEditOtherUsersRecordForbidden(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'bbbb2222-cccc-4ddd-8eee-ffffff000002',
            'user_id' => $this->otherUserId,
            'device_uuid' => 'other-device',
            'name' => 'Other Users Pistol',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'version' => 1,
            'seq' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureRequest();
        $this->put('/api/v1/web/sync-data/bbbb2222-cccc-4ddd-8eee-ffffff000002', json_encode([
            'type' => 'weapons',
            'name' => 'Stolen Rename',
        ]));
        $this->assertResponseCode(403);
    }

    public function testDeleteOwnWeaponSuccess(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'bbbb2222-cccc-4ddd-8eee-ffffff000003',
            'user_id' => $this->userId,
            'device_uuid' => 'test-device',
            'name' => 'To Delete',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'version' => 1,
            'seq' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureRequest();
        $this->delete('/api/v1/web/sync-data/bbbb2222-cccc-4ddd-8eee-ffffff000003?type=weapons');
        $this->assertResponseOk();

        $deleted = $table->get('bbbb2222-cccc-4ddd-8eee-ffffff000003');
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testDeleteOtherUsersRecordForbidden(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $weapon = $table->newEntity([
            'id' => 'bbbb2222-cccc-4ddd-8eee-ffffff000004',
            'user_id' => $this->otherUserId,
            'device_uuid' => 'other-device',
            'name' => 'Other Users Weapon',
            'caliber' => '.308',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'version' => 1,
            'seq' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $table->saveOrFail($weapon);

        $this->configureRequest();
        $this->delete('/api/v1/web/sync-data/bbbb2222-cccc-4ddd-8eee-ffffff000004?type=weapons');
        $this->assertResponseCode(403);
    }

    public function testAmmoStockEditCreatesAdjustmentTransaction(): void
    {
        $ammoTable = TableRegistry::getTableLocator()->get('SyncAmmo');
        $ammo = $ammoTable->newEntity([
            'id' => 'cccc3333-dddd-4eee-8fff-aaaaaa000001',
            'user_id' => $this->userId,
            'device_uuid' => 'test-device',
            'brand' => 'Federal',
            'name' => '9mm FMJ',
            'caliber' => '9mm',
            'grain_weight' => 115,
            'current_stock' => 100,
            'is_archived' => false,
            'version' => 1,
            'seq' => 0,
            'modified_at' => '2026-04-24 12:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $ammoTable->saveOrFail($ammo);

        $this->configureRequest();
        $this->put('/api/v1/web/sync-data/cccc3333-dddd-4eee-8fff-aaaaaa000001', json_encode([
            'type' => 'ammo',
            'current_stock' => 50,
        ]));
        $this->assertResponseOk();

        $txTable = TableRegistry::getTableLocator()->get('SyncAmmoTransactions');
        $transactions = $txTable->find()
            ->where([
                'ammo_uuid' => 'cccc3333-dddd-4eee-8fff-aaaaaa000001',
                'type' => 'adjustment',
            ])
            ->all()
            ->toArray();

        $this->assertCount(1, $transactions);
        $this->assertEquals(-50, $transactions[0]->quantity);

        $updatedAmmo = $ammoTable->get('cccc3333-dddd-4eee-8fff-aaaaaa000001');
        $this->assertEquals(50, $updatedAmmo->current_stock);
    }
}
