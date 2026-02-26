<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;

/**
 * @property \App\Model\Table\RemoteConfigTable $RemoteConfig
 * @property \App\Model\Table\VersionsTable $Versions
 * @property \App\Model\Table\InstancesTable $Instances
 */
class AppConfigController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->RemoteConfig = $this->fetchTable('RemoteConfig');
        $this->Versions = $this->fetchTable('Versions');
        $this->Instances = $this->fetchTable('Instances');
    }

    public function index()
    {
        $this->request->allowMethod(['get']);

        // Use app_instance name from middleware (X-Api-Key mapped), fallback to query param
        $appInstanceName = $this->request->getAttribute('app_instance') ?? $this->request->getQuery('app_instance');
        $version = $this->request->getQuery('version');

        if (!$appInstanceName || !$version) {
            throw new BadRequestException('Missing app_instance or version parameter');
        }

        $instanceObj = $this->Instances->find()->where(['name' => $appInstanceName])->first();
        if (!$instanceObj) {
            throw new \Cake\Http\Exception\NotFoundException('Instance not found');
        }
        if (!$instanceObj->is_active) {
            throw new \Cake\Http\Exception\ForbiddenException('Instance is disabled');
        }

        $instanceId = $instanceObj->id;

        // Check instance-specific version status
        $verSpecific = $this->Versions->find()
            ->where(['instance_id' => $instanceId, 'version' => $version])
            ->first();

        // Check wildcard global version status? (Need to skip or handle global version in instances context)
        // If 'app_instance'='*' was used in db, it might have a null instance_id instead of '*'...
        // Assuming global configs use instance_id IS NULL now
        $verGlobal = $this->Versions->find()
            ->where(['instance_id IS' => null, 'version' => $version])
            ->first();

        $disabled = false;
        if (($verSpecific && $verSpecific->disabled) || ($verGlobal && $verGlobal->disabled)) {
            $disabled = true;
        }

        $configRecord = null;
        if ($verSpecific) {
            $configRecord = $this->RemoteConfig->find()
                ->where(['instance_id' => $instanceId, 'version_id' => $verSpecific->id])
                ->first();
        }

        $configData = new \stdClass(); // base empty object

        if ($configRecord) {
            // Convert to array and filter out metadata columns
            $rawArray = $configRecord->toArray();
            $hiddenCols = ['id', 'instance_id', 'version_id', 'created', 'modified', 'config_data'];
            foreach ($rawArray as $key => $value) {
                if (!in_array($key, $hiddenCols)) {
                    $configData->$key = $value;
                }
            }

            // Merge dynamic JSON config data
            if (!empty($configRecord->config_data)) {
                $dynamicConfig = json_decode($configRecord->config_data, true);
                if (is_array($dynamicConfig)) {
                    foreach ($dynamicConfig as $k => $v) {
                        $configData->$k = $v;
                    }
                }
            }
        } else {
            // Also try to find a global fallback config for this instance (where version_id IS NULL)
            $fallbackConfig = $this->RemoteConfig->find()
                ->where(['instance_id' => $instanceId, 'version_id IS' => null])
                ->first();
            if ($fallbackConfig) {
                $rawArray = $fallbackConfig->toArray();
                $hiddenCols = ['id', 'instance_id', 'version_id', 'created', 'modified', 'config_data'];
                foreach ($rawArray as $key => $value) {
                    if (!in_array($key, $hiddenCols)) {
                        $configData->$key = $value;
                    }
                }

                // Merge dynamic JSON config data
                if (!empty($fallbackConfig->config_data)) {
                    $dynamicConfig = json_decode($fallbackConfig->config_data, true);
                    if (is_array($dynamicConfig)) {
                        foreach ($dynamicConfig as $k => $v) {
                            $configData->$k = $v;
                        }
                    }
                }
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'disabled' => $disabled,
                'config' => $configData
            ]));
    }
}
