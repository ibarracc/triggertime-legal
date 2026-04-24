<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;

class SyncService
{
    /**
     * Table type configuration mapping type keys to table names and ownership strategy.
     *
     * @var array<string, array{table: string, ownership: string, fkField?: string, uuidField?: string}>
     */
    private array $typeConfig = [
        'disciplines' => [
            'table' => 'SyncDisciplines',
            'ownership' => 'direct',
            'fkField' => 'user_id',
        ],
        'phases' => [
            'table' => 'SyncPhases',
            'ownership' => 'via_discipline',
            'fkField' => 'discipline_uuid',
        ],
        'weapons' => [
            'table' => 'SyncWeapons',
            'ownership' => 'direct',
            'fkField' => 'user_id',
        ],
        'ammo' => [
            'table' => 'SyncAmmo',
            'ownership' => 'direct',
            'fkField' => 'user_id',
        ],
        'competitions' => [
            'table' => 'SyncCompetitions',
            'ownership' => 'direct',
            'fkField' => 'user_id',
        ],
        'competition_reminders' => [
            'table' => 'SyncCompetitionReminders',
            'ownership' => 'via_competition',
            'fkField' => 'competition_uuid',
        ],
        'sessions' => [
            'table' => 'SyncSessions',
            'ownership' => 'direct',
            'fkField' => 'user_id',
        ],
        'series' => [
            'table' => 'SyncSeries',
            'ownership' => 'via_session',
            'fkField' => 'session_uuid',
        ],
        'shots' => [
            'table' => 'SyncShots',
            'ownership' => 'via_series',
            'fkField' => 'series_uuid',
        ],
        'strings' => [
            'table' => 'SyncStrings',
            'ownership' => 'via_session',
            'fkField' => 'session_uuid',
        ],
        'ammo_transactions' => [
            'table' => 'SyncAmmoTransactions',
            'ownership' => 'via_ammo',
            'fkField' => 'ammo_uuid',
        ],
    ];

    /**
     * Processing order respecting FK dependencies.
     *
     * @var array<string>
     */
    private array $processingOrder = [
        'disciplines',
        'phases',
        'weapons',
        'ammo',
        'competitions',
        'competition_reminders',
        'sessions',
        'series',
        'shots',
        'strings',
        'ammo_transactions',
    ];

    /**
     * Fields that should not be set from incoming push data.
     *
     * @var array<string>
     */
    private array $excludedFields = [
        'uuid',
        'deleted',
        'created',
        'modified',
    ];

    /**
     * Process incoming push records using last-modified-wins strategy.
     *
     * @param string $userId The user ID.
     * @param string $deviceUuid The device UUID.
     * @param array<string, array<array<string, mixed>>> $records Records keyed by type.
     * @return array{accepted: array<string>, rejected: array<array{uuid: string, reason: string, server_modified_at: string}>, last_sync_at: string}
     */
    public function processPush(string $userId, string $deviceUuid, array $records): array
    {
        $accepted = [];
        $rejected = [];

        foreach ($this->processingOrder as $type) {
            if (empty($records[$type])) {
                continue;
            }

            $config = $this->typeConfig[$type];
            $table = TableRegistry::getTableLocator()->get($config['table']);

            foreach ($records[$type] as $record) {
                $uuid = $record['uuid'];
                $incomingModifiedAt = new DateTime($record['modified_at']);

                // Look up existing record by UUID
                $existing = $table->find()
                    ->where(['id' => $uuid])
                    ->first();

                if ($existing !== null) {
                    $serverModifiedAt = $existing->modified_at instanceof DateTime
                        ? $existing->modified_at
                        : new DateTime((string)$existing->modified_at);

                    // Check if incoming is newer
                    if ($incomingModifiedAt <= $serverModifiedAt) {
                        $rejected[] = [
                            'uuid' => $uuid,
                            'reason' => 'server_newer',
                            'server_modified_at' => $serverModifiedAt->format('Y-m-d\TH:i:sP'),
                        ];
                        continue;
                    }

                    // Handle soft-delete
                    if (!empty($record['deleted'])) {
                        $existing->deleted_at = $incomingModifiedAt;
                        $existing->modified_at = $incomingModifiedAt;
                        $table->saveOrFail($existing);
                        $accepted[] = $uuid;
                        continue;
                    }

                    // Update existing record
                    $data = $this->prepareRecordData($record);
                    $existing = $table->patchEntity($existing, $data);
                    $table->saveOrFail($existing);
                    $accepted[] = $uuid;
                } else {
                    // Insert new record
                    $data = $this->prepareRecordData($record);

                    // Set ownership fields for top-level tables
                    if ($config['ownership'] === 'direct') {
                        $data['user_id'] = $userId;
                        $data['device_uuid'] = $deviceUuid;
                    }

                    $entity = $table->newEntity($data, [
                        'accessibleFields' => ['id' => true],
                    ]);
                    $entity->id = $uuid;

                    // Handle soft-delete on insert
                    if (!empty($record['deleted'])) {
                        $entity->deleted_at = $incomingModifiedAt;
                    }

                    $table->saveOrFail($entity);
                    $accepted[] = $uuid;
                }
            }
        }

        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
            'last_sync_at' => (new DateTime())->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * Pull records modified since the given timestamp.
     *
     * @param string $userId The user ID.
     * @param string $since ISO 8601 timestamp.
     * @param int $limit Maximum records per table type.
     * @return array{records: array<string, array<array<string, mixed>>>, has_more: bool, sync_timestamp: string}
     */
    public function processPull(string $userId, string $since, int $limit = 500): array
    {
        $sinceDate = new DateTime($since);
        $allRecords = [];
        $hasMore = false;

        foreach ($this->processingOrder as $type) {
            $config = $this->typeConfig[$type];
            $table = TableRegistry::getTableLocator()->get($config['table']);

            $query = $table->find()
                ->where(["{$config['table']}.modified_at >" => $sinceDate])
                ->orderBy(["{$config['table']}.modified_at" => 'ASC'])
                ->limit($limit + 1);

            // Apply ownership filter
            $this->applyOwnershipFilter($query, $config, $userId);

            $results = $query->all()->toArray();

            if (count($results) > $limit) {
                $hasMore = true;
                $results = array_slice($results, 0, $limit);
            }

            if (!empty($results)) {
                $allRecords[$type] = array_map(function ($entity) {
                    $data = $entity->toArray();
                    $data['uuid'] = $data['id'];
                    $data['deleted'] = $data['deleted_at'] !== null;
                    unset($data['id']);

                    return $data;
                }, $results);
            }
        }

        return [
            'records' => $allRecords,
            'has_more' => $hasMore,
            'sync_timestamp' => (new DateTime())->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * Check if there are any changes for the given user since the given timestamp.
     *
     * @param string $userId The user ID.
     * @param string $since ISO 8601 timestamp.
     * @return bool
     */
    public function hasChanges(string $userId, string $since): bool
    {
        $sinceDate = new DateTime($since);

        $directTables = ['SyncDisciplines', 'SyncSessions', 'SyncWeapons', 'SyncAmmo', 'SyncCompetitions'];

        foreach ($directTables as $tableName) {
            $table = TableRegistry::getTableLocator()->get($tableName);
            $count = $table->find()
                ->where([
                    'user_id' => $userId,
                    'modified_at >' => $sinceDate,
                ])
                ->count();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare record data for insert/update by removing excluded fields.
     *
     * @param array<string, mixed> $record The incoming record data.
     * @return array<string, mixed>
     */
    private function prepareRecordData(array $record): array
    {
        $data = [];
        foreach ($record as $key => $value) {
            if (!in_array($key, $this->excludedFields, true)) {
                // Convert modified_at string to DateTime object
                if ($key === 'modified_at' && is_string($value)) {
                    $data[$key] = new DateTime($value);
                } else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Apply user ownership filtering to a query based on the table type config.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param array<string, string> $config The type configuration.
     * @param string $userId The user ID.
     * @return void
     */
    private function applyOwnershipFilter(mixed $query, array $config, string $userId): void
    {
        switch ($config['ownership']) {
            case 'direct':
                $query->where(["{$config['table']}.user_id" => $userId]);
                break;

            case 'via_discipline':
                $query->innerJoinWith('SyncDisciplines', function ($q) use ($userId) {
                    return $q->where(['SyncDisciplines.user_id' => $userId]);
                });
                break;

            case 'via_session':
                $query->innerJoinWith('SyncSessions', function ($q) use ($userId) {
                    return $q->where(['SyncSessions.user_id' => $userId]);
                });
                break;

            case 'via_series':
                $query->innerJoinWith('SyncSeries.SyncSessions', function ($q) use ($userId) {
                    return $q->where(['SyncSessions.user_id' => $userId]);
                });
                break;

            case 'via_competition':
                $query->innerJoinWith('SyncCompetitions', function ($q) use ($userId) {
                    return $q->where(['SyncCompetitions.user_id' => $userId]);
                });
                break;

            case 'via_ammo':
                $query->innerJoinWith('SyncAmmo', function ($q) use ($userId) {
                    return $q->where(['SyncAmmo.user_id' => $userId]);
                });
                break;
        }
    }
}
