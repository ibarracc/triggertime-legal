<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncPhases extends BaseMigration
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
        $table = $this->table('sync_phases', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('discipline_uuid', 'uuid', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('default_series_count', 'integer', ['null' => false])
            ->addColumn('default_series_shots', 'integer', ['null' => false])
            ->addColumn('default_series_total_shots', 'integer', ['null' => false])
            ->addColumn('shot_timer_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('wait_seconds', 'integer', ['null' => true])
            ->addColumn('seconds', 'integer', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('allow_sighting_series', 'boolean', ['null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['discipline_uuid'])
            ->addForeignKey('discipline_uuid', 'sync_disciplines', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
