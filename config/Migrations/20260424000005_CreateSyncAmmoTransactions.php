<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncAmmoTransactions extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('sync_ammo_transactions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('ammo_uuid', 'uuid', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('quantity', 'integer', ['null' => false])
            ->addColumn('session_uuid', 'uuid', ['null' => true])
            ->addColumn('weapon_uuid', 'uuid', ['null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['ammo_uuid'])
            ->addIndex(['ammo_uuid', 'modified_at'])
            ->addForeignKey('ammo_uuid', 'sync_ammo', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
