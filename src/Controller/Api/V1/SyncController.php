<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use App\Service\SyncService;
use Cake\Http\Response;

/**
 * @property \App\Model\Table\DevicesTable $Devices
 * @property \App\Model\Table\SubscriptionsTable $Subscriptions
 */
class SyncController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Devices = $this->fetchTable('Devices');
        $this->Subscriptions = $this->fetchTable('Subscriptions');
    }

    /**
     * Push sync records from a device.
     *
     * @return \Cake\Http\Response
     */
    public function push(): Response
    {
        $this->request->allowMethod(['post']);

        $deviceUuid = $this->request->getData('device_uuid');
        $records = $this->request->getData('records', []);

        $userId = $this->authorizeSync((string)$deviceUuid);
        if ($userId instanceof Response) {
            return $userId;
        }

        $syncService = new SyncService();

        $isNewProtocol = $this->detectNewProtocol($records);
        if ($isNewProtocol) {
            $result = $syncService->processPush($userId, (string)$deviceUuid, $records);
        } else {
            $result = $syncService->processPushLegacy($userId, (string)$deviceUuid, $records);
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode($result));
    }

    /**
     * Pull sync records for a device.
     *
     * @return \Cake\Http\Response
     */
    public function pull(): Response
    {
        $this->request->allowMethod(['get']);

        $deviceUuid = $this->request->getQuery('device_uuid');
        $lastSeq = $this->request->getQuery('last_seq');
        $since = $this->request->getQuery('since');

        $userId = $this->authorizeSync((string)$deviceUuid);
        if ($userId instanceof Response) {
            return $userId;
        }

        $syncService = new SyncService();

        if ($lastSeq !== null) {
            $result = $syncService->processPull($userId, (int)$lastSeq);
        } else {
            $result = $syncService->processPullLegacy($userId, (string)$since);
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode($result));
    }

    /**
     * Check if there are changes available for sync.
     *
     * @return \Cake\Http\Response
     */
    public function status(): Response
    {
        $this->request->allowMethod(['get']);

        $deviceUuid = $this->request->getQuery('device_uuid');
        $lastSeq = $this->request->getQuery('last_seq');
        $since = $this->request->getQuery('since', '1970-01-01T00:00:00+00:00');

        $userId = $this->authorizeSync((string)$deviceUuid);
        if ($userId instanceof Response) {
            return $userId;
        }

        $syncService = new SyncService();

        if ($lastSeq !== null) {
            $hasChanges = $syncService->hasChangesSinceSeq($userId, (int)$lastSeq);
        } else {
            $hasChanges = $syncService->hasChanges($userId, (string)$since);
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['has_changes' => $hasChanges]));
    }

    /**
     * Detect if the push request uses the new protocol.
     *
     * The new protocol includes a 'version' field in each record.
     *
     * @param array $records The records from the push request.
     * @return bool True if the new protocol is detected, false otherwise.
     */
    private function detectNewProtocol(array $records): bool
    {
        foreach ($records as $typeRecords) {
            foreach ($typeRecords as $record) {
                if (array_key_exists('version', $record)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Authorize a sync request by verifying the device exists, is linked to a user,
     * and the user has an active PRO subscription.
     *
     * @param string $deviceUuid The device UUID.
     * @return \Cake\Http\Response|string The user ID on success, or a 403 Response on failure.
     */
    private function authorizeSync(string $deviceUuid): string|Response
    {
        if (empty($deviceUuid)) {
            return $this->response->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Missing device_uuid',
                ]));
        }

        $device = $this->Devices->find()
            ->where(['device_uuid' => $deviceUuid])
            ->first();

        if (!$device) {
            return $this->response->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Device not found',
                ]));
        }

        if (empty($device->user_id)) {
            return $this->response->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Device is not linked to an account',
                ]));
        }

        $subscription = $this->Subscriptions->find()
            ->where([
                'user_id' => $device->user_id,
                'plan' => 'pro',
                'status' => 'active',
            ])
            ->first();

        if (!$subscription) {
            return $this->response->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Active PRO subscription required',
                ]));
        }

        return (string)$device->user_id;
    }
}
