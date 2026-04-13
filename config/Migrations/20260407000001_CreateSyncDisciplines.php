<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncDisciplines extends BaseMigration
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
        $table = $this->table('sync_disciplines', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('weapon_type_id', 'integer', ['null' => false])
            ->addColumn('scoring_type_id', 'integer', ['null' => false])
            ->addColumn('use_fm', 'boolean', ['null' => false])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('show_previous_series_on_scoring', 'boolean', ['null' => false])
            ->addColumn('max_score_per_shot', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('x_label', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('always_editable_series', 'boolean', ['null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'modified_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
