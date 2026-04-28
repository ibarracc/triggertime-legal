<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use App\Service\SyncService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Table;

class SyncDataController extends AppController
{
    private const TYPE_MAP = [
        'weapons' => 'SyncWeapons',
        'ammo' => 'SyncAmmo',
        'sessions' => 'SyncSessions',
        'competitions' => 'SyncCompetitions',
        'competition_reminders' => 'SyncCompetitionReminders',
        'ammo_transactions' => 'SyncAmmoTransactions',
    ];

    private const EDITABLE_FIELDS = [
        'weapons' => ['name', 'caliber', 'notes', 'is_favorite', 'is_archived'],
        'ammo' => ['brand', 'name', 'caliber', 'grain_weight', 'cost_per_round', 'current_stock', 'notes', 'is_archived'],
        'sessions' => ['date', 'end_date', 'discipline_name', 'type', 'location', 'notes', 'total_score', 'total_x_count', 'weapon_uuid', 'ammo_uuid'],
        'competitions' => ['name', 'date', 'end_date', 'location', 'discipline_id', 'status', 'notes'],
        'competition_reminders' => ['reminder_date', 'type'],
        'ammo_transactions' => ['type', 'quantity', 'notes'],
    ];

    private const DIRECT_OWNERSHIP = ['weapons', 'ammo', 'sessions', 'competitions'];

    private const VIA_PARENT = [
        'competition_reminders' => ['parent_table' => 'SyncCompetitions', 'fk' => 'competition_uuid'],
        'ammo_transactions' => ['parent_table' => 'SyncAmmo', 'fk' => 'ammo_uuid'],
    ];

    /**
     * Throw a ForbiddenException if the current user is not an admin.
     */
    private function ensureAdmin(): void
    {
        $payload = $this->request->getAttribute('jwt_payload');
        if (!isset($payload['role']) || $payload['role'] !== 'admin') {
            throw new ForbiddenException('Admin access required');
        }
    }

    /**
     * Resolve the ORM table for the given sync type string.
     *
     * @param string $type The sync data type key.
     * @return \Cake\ORM\Table
     * @throws \Cake\Http\Exception\BadRequestException When type is invalid.
     */
    private function resolveTable(string $type): Table
    {
        if (!isset(self::TYPE_MAP[$type])) {
            throw new BadRequestException(
                'Invalid type: ' . $type . '. Valid types: ' . implode(', ', array_keys(self::TYPE_MAP)),
            );
        }

        return $this->fetchTable(self::TYPE_MAP[$type]);
    }

    /**
     * List sync records for a given user and type.
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['get']);

        $type = $this->request->getQuery('type');
        $userId = $this->request->getQuery('user_id');

        if (!$type || !$userId) {
            throw new BadRequestException('Both type and user_id query parameters are required');
        }

        $table = $this->resolveTable($type);

        if (in_array($type, self::DIRECT_OWNERSHIP)) {
            $records = $table->find()
                ->where([
                    $table->getAlias() . '.user_id' => $userId,
                    $table->getAlias() . '.deleted_at IS' => null,
                ])
                ->orderBy([$table->getAlias() . '.modified_at' => 'DESC'])
                ->all();
        } else {
            $parentConfig = self::VIA_PARENT[$type];
            $parentTable = $this->fetchTable($parentConfig['parent_table']);
            $fk = $parentConfig['fk'];

            $parentIds = $parentTable->find()
                ->where(['user_id' => $userId])
                ->select(['id'])
                ->all()
                ->extract('id')
                ->toArray();

            if (empty($parentIds)) {
                return $this->response->withType('application/json')->withStringBody((string)json_encode([
                    'success' => true,
                    'records' => [],
                ]));
            }

            $records = $table->find()
                ->where([
                    $fk . ' IN' => $parentIds,
                    'deleted_at IS' => null,
                ])
                ->orderBy(['modified_at' => 'DESC'])
                ->all();
        }

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'records' => $records,
        ]));
    }

    /**
     * Update allowed fields on a sync record.
     *
     * @param string $id The record UUID.
     * @return \Cake\Http\Response
     */
    public function edit(string $id): Response
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['put', 'patch']);

        $data = $this->request->getData();
        $type = $data['type'] ?? $this->request->getQuery('type');

        if (!$type) {
            throw new BadRequestException('type parameter is required');
        }

        $table = $this->resolveTable($type);

        try {
            $record = $table->get($id);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException('Record not found');
        }

        $allowedFields = self::EDITABLE_FIELDS[$type];
        $patchData = array_intersect_key($data, array_flip($allowedFields));

        $record = $table->patchEntity(
            $record,
            $patchData,
            ['fields' => $allowedFields],
        );

        $syncService = new SyncService();
        $recordUserId = $this->resolveRecordUserId($record, $type);
        $syncService->bumpSeqAndVersion($recordUserId, $record, $table);

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'record' => $record,
        ]));
    }

    /**
     * Resolve the user_id for a record, following parent relationships for child types.
     *
     * @param object $record The record entity.
     * @param string $type The sync data type key.
     * @return string The user ID.
     * @throws \RuntimeException When user_id cannot be determined.
     */
    private function resolveRecordUserId(object $record, string $type): string
    {
        if (property_exists($record, 'user_id') && $record->user_id) {
            return (string)$record->user_id;
        }
        $parentMap = [
            'competition_reminders' => ['SyncCompetitions', 'competition_uuid'],
            'ammo_transactions' => ['SyncAmmo', 'ammo_uuid'],
        ];
        if (isset($parentMap[$type])) {
            [$parentTable, $fk] = $parentMap[$type];
            $parent = $this->fetchTable($parentTable)->get($record->get($fk));

            return (string)$parent->user_id;
        }
        throw new \RuntimeException("Cannot determine user_id for type: $type");
    }

    /**
     * Soft-delete a sync record by setting deleted_at.
     *
     * @param string $id The record UUID.
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->ensureAdmin();
        $this->request->allowMethod(['delete']);

        $type = $this->request->getQuery('type');

        if (!$type) {
            throw new BadRequestException('type query parameter is required');
        }

        $table = $this->resolveTable($type);

        try {
            $record = $table->get($id);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException('Record not found');
        }

        $record->set('deleted_at', DateTime::now());

        $syncService = new SyncService();
        $recordUserId = $this->resolveRecordUserId($record, $type);
        $syncService->bumpSeqAndVersion($recordUserId, $record, $table);

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'message' => 'Record soft-deleted',
        ]));
    }
}
