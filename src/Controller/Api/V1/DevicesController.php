<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;

/**
 * @property \App\Model\Table\ActivationLicensesTable $ActivationLicenses
 * @property \App\Model\Table\DevicesTable $Devices
 * @property \App\Model\Table\SubscriptionsTable $Subscriptions
 * @property \App\Model\Table\InstancesTable $Instances
 */
class DevicesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->ActivationLicenses = $this->fetchTable('ActivationLicenses');
        $this->Devices = $this->fetchTable('Devices');
        $this->Subscriptions = $this->fetchTable('Subscriptions');
        $this->Instances = $this->fetchTable('Instances');
    }

    public function register()
    {
        $this->request->allowMethod(['post']);

        $deviceUuid = $this->request->getData('device_uuid');
        $hardwareModel = $this->request->getData('hardware_model');

        if (empty($deviceUuid) || empty($hardwareModel)) {
            throw new BadRequestException('Missing required fields: device_uuid and hardware_model');
        }

        // app_instance can be explicitly sent, or we fallback to the API Key's app_instance
        $appInstanceName = $this->request->getData('app_instance');
        if (empty($appInstanceName)) {
            $appInstanceName = $this->request->getAttribute('app_instance');
        }

        $instanceObj = null;
        if ($appInstanceName) {
            $instanceObj = $this->Instances->find()->where(['name' => $appInstanceName])->first();
            if ($instanceObj && !$instanceObj->is_active) {
                throw new ForbiddenException('Instance is disabled');
            }
        }

        $device = $this->Devices->find()
            ->where(['device_uuid' => $deviceUuid])
            ->first();

        if (!$device) {
            $device = $this->Devices->newEmptyEntity();
            $device->device_uuid = $deviceUuid;
        }

        $device->hardware_model = $hardwareModel;

        if ($instanceObj !== null) {
            $device->instance_id = $instanceObj->id;
        }

        $fieldsToUpdate = ['platform', 'os_version', 'app_version', 'custom_name'];
        foreach ($fieldsToUpdate as $field) {
            $val = $this->request->getData($field);
            if ($val !== null) {
                $device->$field = $val;
            }
        }

        $this->Devices->save($device);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'message' => 'Device registered successfully',
                'device' => $device
            ]));
    }

    public function activate()
    {
        $this->request->allowMethod(['post']);

        $licenseNumber = $this->request->getData('license_number');
        $deviceUuid = $this->request->getData('device_uuid');
        $appInstanceName = $this->request->getData('app_instance');

        if (!$licenseNumber || !$deviceUuid || !$appInstanceName) {
            throw new BadRequestException('Missing required fields');
        }

        $instanceObj = $this->Instances->find()->where(['name' => $appInstanceName])->first();
        if ($instanceObj && !$instanceObj->is_active) {
            throw new ForbiddenException('Instance is disabled');
        }

        $license = $this->ActivationLicenses->find()
            ->where([
                'license_number' => $licenseNumber,
                'instance_id' => $instanceObj ? $instanceObj->id : null
            ])
            ->first();

        if (!$license) {
            throw new ForbiddenException('Invalid license code');
        }

        $device = $this->Devices->find()->where(['device_uuid' => $deviceUuid])->first();
        if (!$device) {
            // Auto register a wrapper if it doesn't exist yet
            $device = $this->Devices->newEmptyEntity();
            $device->device_uuid = $deviceUuid;
            $this->Devices->save($device);
        }

        $license->device_id = $device->id;
        $license->used = clone new FrozenTime();

        $this->ActivationLicenses->save($license);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'message' => 'Device activated successfully',
                'license' => [
                    'email' => $license->email,
                    'name' => $license->name,
                    'valid' => true
                ]
            ]));
    }

    public function status(string $deviceUuid)
    {
        $this->request->allowMethod(['get']);

        // Check if device exists
        $device = $this->Devices->find()
            ->where(['device_uuid' => $deviceUuid])
            ->first();

        $isActive = false;
        $statusData = [
            'plan' => 'free',
            'source' => null,
        ];

        if ($device && $device->user_id) {
            // Check for active subscriptions via user link
            $subscription = $this->Subscriptions->find()
                ->contain(['Users'])
                ->where(['user_id' => $device->user_id, 'status' => 'active'])
                ->orderDesc('created')
                ->first();

            if ($subscription) {
                $isActive = true;
                $statusData['plan'] = $subscription->plan ?? 'pro';
                $statusData['email'] = $subscription->user->email;
                $statusData['current_period_end'] = $subscription->current_period_end;
                $statusData['cancel_at_period_end'] = $subscription->cancel_at_period_end;
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'pro_active' => $isActive,
                'details' => $statusData
            ]));
    }

    public function licenseStatus(string $deviceUuid)
    {
        $this->request->allowMethod(['get']);

        $device = $this->Devices->find()->where(['device_uuid' => $deviceUuid])->first();
        if (!$device) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'valid' => false]));
        }

        $license = $this->ActivationLicenses->find()
            ->where(['device_id' => $device->id])
            ->first();

        if ($license) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'valid' => true,
                    'license' => [
                        'email' => $license->email,
                        'name' => $license->name,
                        'used' => $license->used
                    ]
                ]));
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'valid' => false]));
    }

    public function transferLicense(string $deviceUuid)
    {
        $this->request->allowMethod(['post']);

        $licenseNumber = $this->request->getData('license_number');
        if (!$licenseNumber) {
            throw new BadRequestException('license_number is required');
        }

        $license = $this->ActivationLicenses->find()
            ->where(['license_number' => $licenseNumber])
            ->first();

        if (!$license) {
            throw new ForbiddenException('Invalid license code');
        }

        $device = $this->Devices->find()->where(['device_uuid' => $deviceUuid])->first();
        if (!$device) {
            $device = $this->Devices->newEmptyEntity();
            $device->device_uuid = $deviceUuid;
            $this->Devices->save($device);
        }

        $license->device_id = $device->id;
        $license->used = clone new FrozenTime();
        $this->ActivationLicenses->save($license);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'message' => 'License transferred successfully'
            ]));
    }
}
