<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAppRemoteConfig extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('app_remote_config', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true])
            ->addColumn('app_instance', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('version_id', 'biginteger', ['null' => true])
            ->addColumn('show_upgrade_button', 'boolean', ['default' => false, 'null' => true])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['app_instance', 'version_id'], ['unique' => true])
            ->addForeignKey('version_id', 'versions', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
