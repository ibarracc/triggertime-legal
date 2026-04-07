<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncShots extends BaseMigration
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
        $table = $this->table('sync_shots', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('series_uuid', 'uuid', ['null' => false])
            ->addColumn('value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('is_x', 'boolean', ['null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['series_uuid'])
            ->addForeignKey('series_uuid', 'sync_series', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
