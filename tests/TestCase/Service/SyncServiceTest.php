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
}
