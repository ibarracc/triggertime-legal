<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\BadRequestException;

/**
 * @property \App\Model\Table\ActivationLicensesTable $ActivationLicenses
 * @property \App\Model\Table\InstancesTable $Instances
 */
class LicensesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->ActivationLicenses = $this->fetchTable('ActivationLicenses');
        $this->Instances = $this->fetchTable('Instances');
    }

    /**
     * Helper to get the base query for licenses an admin is allowed to see.
     */
    private function getAccessibleLicensesQuery()
    {
        $payload = $this->request->getAttribute('jwt_payload');
        $role = $payload['role'] ?? null;
        $userId = $payload['sub'] ?? null;

        $query = $this->ActivationLicenses->find()->contain(['Instances', 'Devices']);

        if ($role === 'club_admin' && $userId) {
            // A club admin can only see licenses attached to instances they manage
            $controlledInstances = $this->Instances->find('list', [
                'idField' => 'id',
                'valueField' => 'id'
            ])->where(['club_admin_id' => $userId])->toArray();

            if (empty($controlledInstances)) {
                // If they manage no instances, they see nothing
                $query->where(['1 = 0']);
            } else {
                $query->where(['ActivationLicenses.instance_id IN' => array_values($controlledInstances)]);
            }
        } elseif ($role !== 'admin') {
            throw new ForbiddenException('Admin access required');
        }

        return $query;
    }

    public function index()
    {
        $this->request->allowMethod(['get']);
        $licenses = $this->getAccessibleLicensesQuery()->all();
        return $this->response->withType('application/json')->withStringBody(json_encode(['success' => true, 'licenses' => $licenses]));
    }

    public function importCsv()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');
        $role = $payload['role'] ?? null;
        $userId = $payload['sub'] ?? null;

        $targetInstanceId = $this->request->getData('instance_id');
        if (!$targetInstanceId) {
            throw new BadRequestException('Target instance ID is required. Please select an instance to load licenses into.');
        }

        $instanceObj = $this->Instances->get($targetInstanceId);
        if ($role === 'club_admin' && $instanceObj->club_admin_id !== $userId) {
            throw new ForbiddenException('You do not have permission to import licenses to this instance');
        }

        $csvString = $this->request->getData('csv_data');
        if (!$csvString) {
            $file = $this->request->getUploadedFile('file');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $csvString = $file->getStream()->getContents();
            }
        }

        if (empty($csvString)) {
            throw new BadRequestException('No CSV data provided');
        }

        $lines = explode("\n", trim($csvString));
        $header = str_getcsv(array_shift($lines));

        $emailIdx = array_search('email', $header);
        $nameIdx = array_search('name', $header);

        if ($emailIdx === false) $emailIdx = 0;
        if ($nameIdx === false) $nameIdx = 1;

        $created = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $row = str_getcsv($line);
            if (!isset($row[$emailIdx])) continue;

            $email = trim($row[$emailIdx]);
            $name = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';

            if (empty($email)) continue;

            $license = $this->ActivationLicenses->newEmptyEntity();
            $license->email = $email;
            $license->name = $name;
            $license->instance_id = $instanceObj->id;

            // Generate XXXX-XXXX-XXXX-XXXX
            $pool = str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 5));
            $rawCode = substr($pool, 0, 16);
            $licenseCode = substr($rawCode, 0, 4) . '-' . substr($rawCode, 4, 4) . '-' . substr($rawCode, 8, 4) . '-' . substr($rawCode, 12, 4);

            $license->license_number = $licenseCode;

            if ($this->ActivationLicenses->save($license)) {
                $created[] = $license;
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'message' => count($created) . ' licenses generated',
                'licenses' => $created
            ]));
    }

    public function view(string $id)
    {
        $this->request->allowMethod(['get']);
        $license = $this->getAccessibleLicensesQuery()->where(['ActivationLicenses.id' => $id])->first();

        if (!$license) {
            throw new \Cake\Http\Exception\NotFoundException('License not found or inaccessible');
        }

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'license' => $license
        ]));
    }

    public function edit(string $id)
    {
        $this->request->allowMethod(['put', 'patch', 'post']);

        $license = $this->getAccessibleLicensesQuery()->where(['ActivationLicenses.id' => $id])->first();
        if (!$license) {
            throw new \Cake\Http\Exception\NotFoundException('License not found or inaccessible');
        }

        $data = $this->request->getData();

        // Ensure license_number cannot be modified after creation
        if (isset($data['license_number'])) {
            unset($data['license_number']);
        }

        $license = $this->ActivationLicenses->patchEntity($license, $data);
        if ($this->ActivationLicenses->save($license)) {
            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'license' => $license
            ]));
        }

        return $this->response->withStatus(400)->withType('application/json')->withStringBody(json_encode([
            'success' => false,
            'errors' => $license->getErrors()
        ]));
    }

    public function toggleActive(string $id)
    {
        $this->request->allowMethod(['post']);

        $license = $this->getAccessibleLicensesQuery()->where(['ActivationLicenses.id' => $id])->first();
        if (!$license) {
            throw new \Cake\Http\Exception\NotFoundException('License not found or inaccessible');
        }

        // Toggle disabled flag (assuming `disabled` or similar column exists. If it relies on deleted_at soft delete, use that)
        // Let's implement active toggle via a soft delete for now if there is no disabled flag
        // Actually, user said "enable/disable", so deleted_at soft delete works perfectly for disabling.

        if ($license->deleted_at !== null) {
            $this->ActivationLicenses->restore($license); // Requires restoring capability from SoftDelete plugin
        } else {
            $this->ActivationLicenses->delete($license); // Triggers soft delete
        }

        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'message' => 'License access toggled'
        ]));
    }
}
