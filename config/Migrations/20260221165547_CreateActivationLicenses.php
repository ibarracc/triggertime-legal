<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateActivationLicenses extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('activation_licenses', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true])
            ->addColumn('app_instance', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('license_number', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('used', 'timestamp', ['null' => true])
            ->addColumn('device_identifier', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('device_information', 'text', ['null' => true])
            ->addColumn('user_id', 'uuid', ['null' => true])
            ->addColumn('created', 'timestamp', [
                'null' => false,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => false,
            ])
            ->addIndex(['license_number'], ['unique' => true])
            ->addIndex(['email', 'app_instance'], ['unique' => true, 'name' => 'licenses_email_app_key'])
            ->addIndex(['app_instance'], ['name' => 'idx_lic_app'])
            ->addIndex(['device_identifier'], ['name' => 'idx_lic_device'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
