<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncSessions extends BaseMigration
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
        $table = $this->table('sync_sessions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('date', 'datetime', ['null' => false])
            ->addColumn('end_date', 'datetime', ['null' => true])
            ->addColumn('discipline_uuid', 'uuid', ['null' => true])
            ->addColumn('discipline_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('total_score', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'null' => false])
            ->addColumn('total_x_count', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('event_uuid', 'uuid', ['null' => true])
            ->addColumn('category_uuid', 'uuid', ['null' => true])
            ->addColumn('scoring_type_id', 'integer', ['null' => false])
            ->addColumn('auto_closed', 'boolean', ['null' => false])
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
