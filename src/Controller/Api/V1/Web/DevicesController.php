<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Cake\Utility\Text;

/**
 * @property \App\Model\Table\DevicesTable $Devices
 * @property \App\Model\Table\SubscriptionsTable $Subscriptions
 * @property \App\Model\Table\UpgradeTokensTable $UpgradeTokens
 */
class DevicesController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Devices = $this->fetchTable('Devices');
        $this->Subscriptions = $this->fetchTable('Subscriptions');
        $this->UpgradeTokens = $this->fetchTable('UpgradeTokens');
    }

    /**
     * List all devices belonging to the authenticated user.
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $devices = $this->Devices->find()
            ->where(['user_id' => $userId])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'devices' => $devices,
            ]));
    }

    /**
     * Link a device to the authenticated user's account using a link code.
     */
    public function link()
    {
        $this->request->allowMethod(['post']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];
        $linkCode = $this->request->getData('link_code');

        if (!$linkCode) {
            throw new BadRequestException('Link code is required');
        }

        // Get active subscription
        $subscription = $this->Subscriptions->find()
            ->where(['user_id' => $userId, 'status' => 'active'])
            ->orderDesc('created_at')
            ->first();

        if (!$subscription) {
            throw new ForbiddenException('No active subscription found');
        }

        // Verify the link code
        $token = $this->UpgradeTokens->find()
            ->where(['token_string' => strtoupper($linkCode), 'type' => 'link'])
            ->first();

        if (!$token || $token->is_used || $token->expires_at < new DateTime()) {
            throw new BadRequestException('Invalid or expired link code');
        }

        $deviceUuid = $token->device_uuid;

        // Find or create device wrapper
        $device = $this->Devices->find()->where(['device_uuid' => $deviceUuid])->first();
        if (!$device) {
            // Auto-create it if it hasn't registered yet for some reason
            $device = $this->Devices->newEmptyEntity();
            $device->id = Text::uuid();
            $device->device_uuid = $deviceUuid;
            $device->user_id = $userId;
            $this->Devices->save($device);
        } else {
            // Claim ownership if unassigned
            if (!$device->user_id) {
                $device->user_id = $userId;
                $this->Devices->save($device);
            } elseif ($device->user_id !== $userId) {
                throw new ForbiddenException('Device is logged into another account');
            }
        }

        // Mark token as used
        $token->is_used = true;
        $this->UpgradeTokens->save($token);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true, 'device' => $device]));
    }

    /**
     * Unlink a device from the authenticated user's account.
     */
    public function unlink(string $deviceUuid)
    {
        $this->request->allowMethod(['post']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $device = $this->Devices->find()
            ->where(['device_uuid' => $deviceUuid, 'user_id' => $userId])
            ->first();

        if (!$device) {
            throw new NotFoundException('Device not found or not owned by you');
        }

        // Soft delete the device
        $this->Devices->delete($device);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true]));
    }

    /**
     * Link a device to the user's account using an upgrade token.
     */
    public function linkUpgradeToken()
    {
        $this->request->allowMethod(['post']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];
        $upgradeTokenString = $this->request->getData('upgrade_token');

        if (!$upgradeTokenString) {
            throw new BadRequestException('Upgrade token is required');
        }

        // Get active subscription
        $subscription = $this->Subscriptions->find()
            ->where(['user_id' => $userId, 'status' => 'active', 'plan' => 'pro'])
            ->orderDesc('created')
            ->first();

        if (!$subscription) {
            throw new ForbiddenException('No active Pro+ subscription found');
        }

        // Verify the upgrade token
        $token = $this->UpgradeTokens->find()
            ->where(['token_string' => $upgradeTokenString, 'type' => 'upgrade'])
            ->first();

        if (!$token || $token->is_used || $token->expires_at < new DateTime()) {
            throw new BadRequestException('Invalid or expired upgrade token');
        }

        $deviceUuid = $token->device_uuid;

        // Find or create device wrapper
        $device = $this->Devices->find()->where(['device_uuid' => $deviceUuid])->first();
        if (!$device) {
            $device = $this->Devices->newEmptyEntity();
            $device->id = Text::uuid();
            $device->device_uuid = $deviceUuid;
        }
        $device->user_id = $userId;
        $this->Devices->save($device);

        // Mark token as used
        $token->is_used = true;
        $this->UpgradeTokens->save($token);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true, 'device' => $device]));
    }

    /**
     * Update a device's details for the authenticated user.
     */
    public function update(string $deviceUuid)
    {
        $this->request->allowMethod(['put']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $device = $this->Devices->find()
            ->where(['device_uuid' => $deviceUuid, 'user_id' => $userId])
            ->first();

        if (!$device) {
            throw new NotFoundException('Device not found or not owned by you');
        }

        $customName = $this->request->getData('custom_name');
        if ($customName !== null) {
            $device->custom_name = $customName;
        }

        $this->Devices->save($device);

        // Fetch user object to mirror payload from AppController indexing behavior
        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'device' => $device,
            ]));
    }
}
