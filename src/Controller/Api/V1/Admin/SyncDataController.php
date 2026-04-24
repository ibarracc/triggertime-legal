<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
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
        'competitions' => 'SyncCompetitions',
        'competition_reminders' => 'SyncCompetitionReminders',
        'ammo_transactions' => 'SyncAmmoTransactions',
    ];

    private const EDITABLE_FIELDS = [
        'weapons' => ['name', 'caliber', 'notes', 'is_favorite', 'is_archived'],
        'ammo' => ['brand', 'name', 'caliber', 'grain_weight', 'current_stock', 'notes', 'is_archived'],
        'competitions' => ['name', 'date', 'end_date', 'location', 'discipline_id', 'status', 'notes'],
        'competition_reminders' => ['reminder_date', 'type'],
        'ammo_transactions' => ['type', 'quantity', 'notes'],
    ];

    private const DIRECT_OWNERSHIP = ['weapons', 'ammo', 'competitions'];

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
        $patchData['modified_at'] = DateTime::now();

        $record = $table->patchEntity(
            $record,
            $patchData,
            ['fields' => array_merge($allowedFields, ['modified_at'])],
        );

        if ($table->save($record)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
                'record' => $record,
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'errors' => $record->getErrors(),
        ]));
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

        $now = DateTime::now();
        $record->set('deleted_at', $now);
        $record->set('modified_at', $now);

        if ($table->save($record)) {
            return $this->response->withType('application/json')->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Record soft-deleted',
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody((string)json_encode([
            'success' => false,
            'message' => 'Failed to delete record',
        ]));
    }
}
