<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateInstances extends BaseMigration
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
        $table = $this->table('instances', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('club_admin_id', 'uuid', [
                'null' => true,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['name'], ['unique' => true])
            ->addForeignKey('club_admin_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
