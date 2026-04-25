<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddUuidFksToSyncSessions extends BaseMigration
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
        $table = $this->table('sync_sessions');
        $table->addColumn('weapon_uuid', 'uuid', ['null' => true])
            ->addColumn('ammo_uuid', 'uuid', ['null' => true])
            ->addColumn('competition_uuid', 'uuid', ['null' => true])
            ->update();
    }
}
