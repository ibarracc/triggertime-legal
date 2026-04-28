<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use App\Service\SyncService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Table;
use Cake\Utility\Text;

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

    private const DIRECT_OWNERSHIP = ['weapons', 'ammo', 'sessions', 'competitions'];

    private const VIA_PARENT = [
        'competition_reminders' => ['parent_table' => 'SyncCompetitions', 'fk' => 'competition_uuid'],
        'ammo_transactions' => ['parent_table' => 'SyncAmmo', 'fk' => 'ammo_uuid'],
    ];

    private const EDITABLE_FIELDS = [
        'weapons' => ['name', 'caliber', 'notes', 'is_favorite', 'is_archived'],
        'ammo' => ['brand', 'name', 'caliber', 'grain_weight', 'cost_per_round', 'notes', 'is_archived'],
        'sessions' => ['notes', 'location'],
        'competitions' => ['name', 'date', 'end_date', 'location', 'status', 'notes'],
        'competition_reminders' => ['reminder_offset', 'is_enabled'],
    ];

    /**
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
     * List sync records for the authenticated user by type.
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $type = $this->request->getQuery('type');
        if (!$type) {
            throw new BadRequestException('type query parameter is required');
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
     * Edit a sync record.
     *
     * @param string $uuid The record UUID.
     * @return \Cake\Http\Response
     */
    public function edit(string $uuid): Response
    {
        $this->request->allowMethod(['put', 'patch']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $data = $this->request->getData();
        $type = $data['type'] ?? $this->request->getQuery('type');

        if (!$type || !isset(self::EDITABLE_FIELDS[$type])) {
            throw new BadRequestException('Valid type parameter is required');
        }

        $table = $this->resolveTable($type);

        try {
            $record = $table->get($uuid);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException('Record not found');
        }

        $this->verifyOwnership($record, $type, $userId);

        if ($type === 'ammo' && isset($data['current_stock'])) {
            $this->handleAmmoStockEdit($record, (int)$data['current_stock'], $userId);
            unset($data['current_stock']);
        }

        $allowedFields = self::EDITABLE_FIELDS[$type];
        $patchData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($patchData)) {
            $syncService = new SyncService();
            $record = $table->patchEntity($record, $patchData, [
                'fields' => $allowedFields,
            ]);
            $syncService->bumpSeqAndVersion($userId, $record, $table);
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'record' => $record,
            ]));
    }

    /**
     * Soft-delete a sync record.
     *
     * @param string $uuid The record UUID.
     * @return \Cake\Http\Response
     */
    public function delete(string $uuid): Response
    {
        $this->request->allowMethod(['delete']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $type = $this->request->getQuery('type');
        if (!$type) {
            throw new BadRequestException('type query parameter is required');
        }

        $table = $this->resolveTable($type);

        try {
            $record = $table->get($uuid);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException('Record not found');
        }

        $this->verifyOwnership($record, $type, $userId);

        $syncService = new SyncService();
        $record->set('deleted_at', new \Cake\I18n\DateTime());
        $syncService->bumpSeqAndVersion($userId, $record, $table);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Record deleted',
            ]));
    }

    /**
     * Verify the authenticated user owns the given record.
     *
     * @param object $record The record entity.
     * @param string $type The sync data type key.
     * @param string $userId The authenticated user ID.
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException When the user does not own the record.
     */
    private function verifyOwnership(object $record, string $type, string $userId): void
    {
        if (in_array($type, self::DIRECT_OWNERSHIP)) {
            if ((string)$record->user_id !== $userId) {
                throw new ForbiddenException('Not authorized to modify this record');
            }
        } else {
            $parentConfig = self::VIA_PARENT[$type];
            $parentTable = $this->fetchTable($parentConfig['parent_table']);
            $fk = $parentConfig['fk'];
            $parentId = $record->get($fk);
            $parent = $parentTable->find()->where(['id' => $parentId, 'user_id' => $userId])->first();
            if (!$parent) {
                throw new ForbiddenException('Not authorized to modify this record');
            }
        }
    }

    /**
     * Handle a stock adjustment when editing an ammo record's current_stock.
     *
     * @param object $ammoRecord The ammo entity.
     * @param int $newStock The desired new stock value.
     * @param string $userId The authenticated user ID.
     * @return void
     */
    private function handleAmmoStockEdit(object $ammoRecord, int $newStock, string $userId): void
    {
        $currentStock = (int)$ammoRecord->current_stock;
        $delta = $newStock - $currentStock;

        if ($delta === 0) {
            return;
        }

        $txTable = $this->fetchTable('SyncAmmoTransactions');
        $syncService = new SyncService();

        $txEntity = $txTable->newEntity([
            'ammo_uuid' => $ammoRecord->id,
            'type' => 'adjustment',
            'quantity' => $delta,
            'notes' => null,
            'modified_at' => new \Cake\I18n\DateTime(),
        ], ['accessibleFields' => ['id' => true]]);
        $txEntity->id = Text::uuid();

        $newSeq = $syncService->bumpSeqPublic($userId);
        $txEntity->version = 1;
        $txEntity->seq = $newSeq;
        $txTable->saveOrFail($txEntity);

        $ammoRecord->current_stock = $newStock;
    }
}
