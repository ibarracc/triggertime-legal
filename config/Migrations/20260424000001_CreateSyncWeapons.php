<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncWeapons extends BaseMigration
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
        $table = $this->table('sync_weapons', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('caliber', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('serial_number', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('is_favorite', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('is_archived', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('shot_count', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'modified_at'])
            ->addIndex(['user_id', 'deleted_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
