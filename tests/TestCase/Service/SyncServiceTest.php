<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SyncService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SyncServiceTest extends TestCase
{
    protected SyncService $service;

    protected array $fixtures = [
        'app.Users',
        'app.SyncDisciplines',
        'app.SyncPhases',
        'app.SyncSessions',
        'app.SyncSeries',
        'app.SyncShots',
        'app.SyncStrings',
        'app.SyncWeapons',
        'app.SyncAmmo',
        'app.SyncCompetitions',
        'app.SyncCompetitionReminders',
        'app.SyncAmmoTransactions',
    ];

    /**
     * User ID from UsersFixture
     */
    private string $userId = 'c3792a3c-af61-479e-aaa3-16e763aacbf8';

    private string $deviceUuid = 'test-device-uuid-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncService();
    }

    public function testPushInsertsNewDiscipline(): void
    {
        $uuid = 'a1a1a1a1-b2b2-4c3c-8d4d-e5e5e5e5e5e1';
        $records = [
            'disciplines' => [
                [
                    'uuid' => $uuid,
                    'name' => 'Air Pistol',
                    'weapon_type_id' => 1,
                    'scoring_type_id' => 1,
                    'use_fm' => false,
                    'active' => true,
                    'show_previous_series_on_scoring' => false,
                    'max_score_per_shot' => 10.9,
                    'x_label' => null,
                    'always_editable_series' => false,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertContains($uuid, $result['accepted']);
        $this->assertEmpty($result['rejected']);
        $this->assertArrayHasKey('last_sync_at', $result);

        // Verify in DB
        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $entity = $table->get($uuid);
        $this->assertSame('Air Pistol', $entity->name);
        $this->assertSame($this->userId, $entity->user_id);
        $this->assertSame($this->deviceUuid, $entity->device_uuid);
    }

    public function testPushUpdatesNewerRecord(): void
    {
        // Insert a session with old modified_at
        $uuid = 'b2b2b2b2-c3c3-4d4d-8e5e-f6f6f6f6f6f2';
        $table = TableRegistry::getTableLocator()->get('SyncSessions');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'date' => '2026-03-15 10:00:00',
            'discipline_name' => 'Air Pistol',
            'type' => 'practice',
            'total_score' => 100,
            'total_x_count' => 0,
            'scoring_type_id' => 1,
            'auto_closed' => false,
            'modified_at' => '2026-03-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        // Push newer version
        $records = [
            'sessions' => [
                [
                    'uuid' => $uuid,
                    'date' => '2026-03-15T10:00:00+00:00',
                    'discipline_name' => 'Air Pistol Updated',
                    'type' => 'practice',
                    'total_score' => 200,
                    'total_x_count' => 5,
                    'scoring_type_id' => 1,
                    'auto_closed' => false,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertContains($uuid, $result['accepted']);

        // Verify updated in DB
        $updated = $table->get($uuid);
        $this->assertSame('Air Pistol Updated', $updated->discipline_name);
        $this->assertEquals(200, (float)$updated->total_score);
    }

    public function testPushRejectsOlderRecord(): void
    {
        // Insert a session with newer modified_at
        $uuid = 'c3c3c3c3-d4d4-4e5e-8f6f-a7a7a7a7a7a3';
        $table = TableRegistry::getTableLocator()->get('SyncSessions');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'date' => '2026-03-15 10:00:00',
            'discipline_name' => 'Air Pistol',
            'type' => 'practice',
            'total_score' => 100,
            'total_x_count' => 0,
            'scoring_type_id' => 1,
            'auto_closed' => false,
            'modified_at' => '2026-04-05 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        // Push older version
        $records = [
            'sessions' => [
                [
                    'uuid' => $uuid,
                    'date' => '2026-03-15T10:00:00+00:00',
                    'discipline_name' => 'Should Not Update',
                    'type' => 'practice',
                    'total_score' => 999,
                    'total_x_count' => 99,
                    'scoring_type_id' => 1,
                    'auto_closed' => false,
                    'modified_at' => '2026-03-01T10:00:00+00:00',
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertNotContains($uuid, $result['accepted']);
        $this->assertCount(1, $result['rejected']);
        $this->assertSame($uuid, $result['rejected'][0]['uuid']);
        $this->assertSame('server_newer', $result['rejected'][0]['reason']);
        $this->assertArrayHasKey('server_modified_at', $result['rejected'][0]);

        // Verify NOT updated in DB
        $existing = $table->get($uuid);
        $this->assertSame('Air Pistol', $existing->discipline_name);
    }

    public function testPushSoftDeletesRecord(): void
    {
        // Insert a session
        $uuid = 'd4d4d4d4-e5e5-4f6f-8a7a-b8b8b8b8b8b4';
        $table = TableRegistry::getTableLocator()->get('SyncSessions');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'date' => '2026-03-15 10:00:00',
            'discipline_name' => 'Air Pistol',
            'type' => 'practice',
            'total_score' => 100,
            'total_x_count' => 0,
            'scoring_type_id' => 1,
            'auto_closed' => false,
            'modified_at' => '2026-03-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        // Push with deleted: true and newer modified_at
        $records = [
            'sessions' => [
                [
                    'uuid' => $uuid,
                    'date' => '2026-03-15T10:00:00+00:00',
                    'discipline_name' => 'Air Pistol',
                    'type' => 'practice',
                    'total_score' => 100,
                    'total_x_count' => 0,
                    'scoring_type_id' => 1,
                    'auto_closed' => false,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                    'deleted' => true,
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertContains($uuid, $result['accepted']);

        // Verify soft-deleted in DB
        $deleted = $table->get($uuid);
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testPullReturnsRecordsAfterSince(): void
    {
        // Insert a discipline
        $uuid = 'e5e5e5e5-f6f6-4a7a-8b8b-c9c9c9c9c9c5';
        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'name' => 'Air Rifle',
            'weapon_type_id' => 2,
            'scoring_type_id' => 1,
            'use_fm' => false,
            'active' => true,
            'show_previous_series_on_scoring' => false,
            'always_editable_series' => false,
            'modified_at' => '2026-04-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        $result = $this->service->processPull($this->userId, '2026-03-01T00:00:00+00:00');

        $this->assertArrayHasKey('records', $result);
        $this->assertArrayHasKey('disciplines', $result['records']);

        $disciplineUuids = array_column($result['records']['disciplines'], 'uuid');
        $this->assertContains($uuid, $disciplineUuids);
        $this->assertArrayHasKey('sync_timestamp', $result);
    }

    public function testPullExcludesOlderRecords(): void
    {
        // Insert a discipline with old modified_at
        $uuid = 'f6f6f6f6-a7a7-4b8b-8c9c-d0d0d0d0d0d6';
        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'name' => 'Trap',
            'weapon_type_id' => 3,
            'scoring_type_id' => 2,
            'use_fm' => false,
            'active' => true,
            'show_previous_series_on_scoring' => false,
            'always_editable_series' => false,
            'modified_at' => '2026-01-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        // Pull with since AFTER the record's modified_at
        $result = $this->service->processPull($this->userId, '2026-06-01T00:00:00+00:00');

        $disciplineUuids = array_column($result['records']['disciplines'] ?? [], 'uuid');
        $this->assertNotContains($uuid, $disciplineUuids);
    }

    public function testHasChangesReturnsTrueWhenChangesExist(): void
    {
        // Insert a discipline
        $uuid = 'a7a7a7a7-b8b8-4c9c-8d0d-e1e1e1e1e1e7';
        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'name' => 'Skeet',
            'weapon_type_id' => 3,
            'scoring_type_id' => 2,
            'use_fm' => false,
            'active' => true,
            'show_previous_series_on_scoring' => false,
            'always_editable_series' => false,
            'modified_at' => '2026-04-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        $result = $this->service->hasChanges($this->userId, '2026-03-01T00:00:00+00:00');
        $this->assertTrue($result);
    }

    public function testHasChangesReturnsFalseWhenNoChanges(): void
    {
        $result = $this->service->hasChanges($this->userId, '2099-01-01T00:00:00+00:00');
        $this->assertFalse($result);
    }

    public function testPullIncludesSoftDeletedRecords(): void
    {
        $uuid = 'b8b8b8b8-c9c9-4d0d-8e1e-f2f2f2f2f2f8';
        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'name' => 'Deleted Discipline',
            'weapon_type_id' => 1,
            'scoring_type_id' => 1,
            'use_fm' => false,
            'active' => true,
            'show_previous_series_on_scoring' => false,
            'always_editable_series' => false,
            'modified_at' => '2026-04-01 10:00:00',
            'deleted_at' => '2026-04-02 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        $result = $this->service->processPull($this->userId, '2026-03-01T00:00:00+00:00');

        $this->assertArrayHasKey('disciplines', $result['records']);
        $disciplines = $result['records']['disciplines'];
        $found = array_filter($disciplines, fn($d) => $d['uuid'] === $uuid);
        $this->assertNotEmpty($found);

        $record = array_values($found)[0];
        $this->assertTrue($record['deleted']);
    }

    public function testPullRespectsPagination(): void
    {
        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $uuids = [
            'c9c9c9c9-d0d0-4e1e-8f2f-a3a3a3a3a3a9',
            'd0d0d0d0-e1e1-4f2f-8a3a-b4b4b4b4b4b0',
            'e1e1e1e1-f2f2-4a3a-8b4b-c5c5c5c5c5c1',
        ];

        foreach ($uuids as $i => $uuid) {
            $day = $i + 1;
            $entity = $table->newEntity([
                'user_id' => $this->userId,
                'device_uuid' => $this->deviceUuid,
                'name' => "Discipline {$i}",
                'weapon_type_id' => 1,
                'scoring_type_id' => 1,
                'use_fm' => false,
                'active' => true,
                'show_previous_series_on_scoring' => false,
                'always_editable_series' => false,
                'modified_at' => "2026-04-0{$day} 10:00:00",
            ], ['accessibleFields' => ['id' => true]]);
            $entity->id = $uuid;
            $table->saveOrFail($entity);
        }

        $result = $this->service->processPull($this->userId, '2026-03-01T00:00:00+00:00', 2);

        $this->assertTrue($result['has_more']);
        $this->assertArrayHasKey('disciplines', $result['records']);
        $this->assertCount(2, $result['records']['disciplines']);
    }

    public function testPullExcludesOtherUserRecords(): void
    {
        $user1Id = $this->userId;
        $user2Id = 'f2f2f2f2-a3a3-4b4b-8c5c-d6d6d6d6d6d2';

        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');

        $uuid1 = 'a3a3a3a3-b4b4-4c5c-8d6d-e7e7e7e7e7e3';
        $entity1 = $table->newEntity([
            'user_id' => $user1Id,
            'device_uuid' => $this->deviceUuid,
            'name' => 'User1 Discipline',
            'weapon_type_id' => 1,
            'scoring_type_id' => 1,
            'use_fm' => false,
            'active' => true,
            'show_previous_series_on_scoring' => false,
            'always_editable_series' => false,
            'modified_at' => '2026-04-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity1->id = $uuid1;
        $table->saveOrFail($entity1);

        $uuid2 = 'b4b4b4b4-c5c5-4d6d-8e7e-f8f8f8f8f8f4';
        $entity2 = $table->newEntity([
            'user_id' => $user2Id,
            'device_uuid' => $this->deviceUuid,
            'name' => 'User2 Discipline',
            'weapon_type_id' => 1,
            'scoring_type_id' => 1,
            'use_fm' => false,
            'active' => true,
            'show_previous_series_on_scoring' => false,
            'always_editable_series' => false,
            'modified_at' => '2026-04-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity2->id = $uuid2;
        $table->saveOrFail($entity2);

        $result = $this->service->processPull($user1Id, '2026-03-01T00:00:00+00:00');

        $this->assertArrayHasKey('disciplines', $result['records']);
        $disciplineUuids = array_column($result['records']['disciplines'], 'uuid');
        $this->assertContains($uuid1, $disciplineUuids);
        $this->assertNotContains($uuid2, $disciplineUuids);
    }

    public function testPushInsertsNewWeapon(): void
    {
        $uuid = 'w1w1w1w1-a2a2-4b3b-8c4c-d5d5d5d5d5d1';
        $records = [
            'weapons' => [
                [
                    'uuid' => $uuid,
                    'name' => 'CZ Shadow 2',
                    'caliber' => '9mm',
                    'serial_number' => 'SN12345',
                    'notes' => null,
                    'is_favorite' => true,
                    'is_archived' => false,
                    'shot_count' => 1500,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertContains($uuid, $result['accepted']);

        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $entity = $table->get($uuid);
        $this->assertSame('CZ Shadow 2', $entity->name);
        $this->assertSame('9mm', $entity->caliber);
        $this->assertSame($this->userId, $entity->user_id);
        $this->assertSame(1500, $entity->shot_count);
    }

    public function testPullReturnsWeapons(): void
    {
        $uuid = 'w2w2w2w2-b3b3-4c4c-8d5d-e6e6e6e6e6e2';
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'name' => 'Glock 17',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        $result = $this->service->processPull($this->userId, '2026-03-01T00:00:00+00:00');

        $this->assertArrayHasKey('weapons', $result['records']);
        $weaponUuids = array_column($result['records']['weapons'], 'uuid');
        $this->assertContains($uuid, $weaponUuids);
    }

    public function testPushInsertsCompetitionAndReminder(): void
    {
        $compUuid = 'cp1cp1cp-a2a2-4b3b-8c4c-d5d5d5d5d5c1';
        $remUuid = 'cr1cr1cr-b3b3-4c4c-8d5d-e6e6e6e6e6r1';
        $records = [
            'competitions' => [
                [
                    'uuid' => $compUuid,
                    'name' => 'IPSC Level 2',
                    'date' => '2026-06-15',
                    'end_date' => '2026-06-16',
                    'location' => 'Madrid',
                    'discipline_id' => null,
                    'status' => 'registered',
                    'notes' => null,
                    'is_active' => true,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
            'competition_reminders' => [
                [
                    'uuid' => $remUuid,
                    'competition_uuid' => $compUuid,
                    'reminder_offset' => 1440,
                    'is_enabled' => true,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertContains($compUuid, $result['accepted']);
        $this->assertContains($remUuid, $result['accepted']);

        $compTable = TableRegistry::getTableLocator()->get('SyncCompetitions');
        $comp = $compTable->get($compUuid);
        $this->assertSame('IPSC Level 2', $comp->name);
        $this->assertSame($this->userId, $comp->user_id);

        $remTable = TableRegistry::getTableLocator()->get('SyncCompetitionReminders');
        $rem = $remTable->get($remUuid);
        $this->assertSame($compUuid, $rem->competition_uuid);
        $this->assertSame(1440, $rem->reminder_offset);
    }

    public function testPushInsertsAmmoAndTransaction(): void
    {
        $ammoUuid = 'am1am1am-a2a2-4b3b-8c4c-d5d5d5d5d5a1';
        $txUuid = 'at1at1at-b3b3-4c4c-8d5d-e6e6e6e6e6t1';
        $records = [
            'ammo' => [
                [
                    'uuid' => $ammoUuid,
                    'brand' => 'Sellier & Bellot',
                    'name' => 'FMJ',
                    'caliber' => '9mm',
                    'grain_weight' => 124,
                    'cost_per_round' => 0.22,
                    'current_stock' => 500,
                    'is_archived' => false,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
            'ammo_transactions' => [
                [
                    'uuid' => $txUuid,
                    'ammo_uuid' => $ammoUuid,
                    'type' => 'purchase',
                    'quantity' => 500,
                    'session_uuid' => null,
                    'weapon_uuid' => null,
                    'notes' => null,
                    'modified_at' => '2026-04-01T10:00:00+00:00',
                ],
            ],
        ];

        $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

        $this->assertContains($ammoUuid, $result['accepted']);
        $this->assertContains($txUuid, $result['accepted']);

        $ammoTable = TableRegistry::getTableLocator()->get('SyncAmmo');
        $ammo = $ammoTable->get($ammoUuid);
        $this->assertSame('Sellier & Bellot', $ammo->brand);

        $txTable = TableRegistry::getTableLocator()->get('SyncAmmoTransactions');
        $tx = $txTable->get($txUuid);
        $this->assertSame($ammoUuid, $tx->ammo_uuid);
        $this->assertSame(500, $tx->quantity);
    }

    public function testHasChangesDetectsWeaponChanges(): void
    {
        $uuid = 'w4w4w4w4-d5d5-4e6e-8f7f-a8a8a8a8a8w4';
        $table = TableRegistry::getTableLocator()->get('SyncWeapons');
        $entity = $table->newEntity([
            'user_id' => $this->userId,
            'device_uuid' => $this->deviceUuid,
            'name' => 'Beretta 92',
            'caliber' => '9mm',
            'is_favorite' => false,
            'is_archived' => false,
            'shot_count' => 0,
            'modified_at' => '2026-04-01 10:00:00',
        ], ['accessibleFields' => ['id' => true]]);
        $entity->id = $uuid;
        $table->saveOrFail($entity);

        $result = $this->service->hasChanges($this->userId, '2026-03-01T00:00:00+00:00');
        $this->assertTrue($result);
    }
}
