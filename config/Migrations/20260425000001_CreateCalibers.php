<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCalibers extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('calibers');
        $table->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('weapon_category', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('standard', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('sort_order', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['name', 'weapon_category', 'standard'], ['unique' => true])
            ->create();
    }
}
