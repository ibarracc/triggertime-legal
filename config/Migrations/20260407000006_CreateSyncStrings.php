<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncStrings extends BaseMigration
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
        $table = $this->table('sync_strings', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('session_uuid', 'uuid', ['null' => false])
            ->addColumn('phase_uuid', 'uuid', ['null' => true])
            ->addColumn('string_number_within_phase', 'integer', ['null' => false])
            ->addColumn('total_score', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('x_count', 'integer', ['null' => true])
            ->addColumn('first_miss', 'integer', ['null' => true])
            ->addColumn('is_sighting', 'boolean', ['null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['session_uuid'])
            ->addIndex(['session_uuid', 'modified_at'])
            ->addForeignKey('session_uuid', 'sync_sessions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
