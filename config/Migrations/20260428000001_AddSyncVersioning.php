<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSyncVersioning extends BaseMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        $table = $this->table('user_sync_sequences', ['id' => false, 'primary_key' => ['user_id']]);
        $table->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('current_seq', 'biginteger', ['default' => 0, 'null' => false])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $syncTables = [
            'sync_disciplines',
            'sync_phases',
            'sync_sessions',
            'sync_series',
            'sync_shots',
            'sync_strings',
            'sync_weapons',
            'sync_ammo',
            'sync_competitions',
            'sync_competition_reminders',
            'sync_ammo_transactions',
        ];

        foreach ($syncTables as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('seq', 'biginteger', ['default' => 0, 'null' => false])
                ->addColumn('version', 'integer', ['default' => 1, 'null' => false])
                ->addIndex(['seq'])
                ->update();
        }
    }
}
