<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncCompetitionReminders extends BaseMigration
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
        $table = $this->table('sync_competition_reminders', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('competition_uuid', 'uuid', ['null' => false])
            ->addColumn('reminder_offset', 'integer', ['null' => false])
            ->addColumn('is_enabled', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['competition_uuid'])
            ->addIndex(['competition_uuid', 'modified_at'])
            ->addForeignKey('competition_uuid', 'sync_competitions', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
