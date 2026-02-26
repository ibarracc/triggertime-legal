<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateDevices extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('devices', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('device_uuid', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('custom_name', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('hardware_model', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('first_activation_date', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => true,
            ])
            ->addColumn('user_id', 'uuid', [
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['device_uuid'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
