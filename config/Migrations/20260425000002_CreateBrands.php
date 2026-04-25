<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBrands extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('brands');
        $table->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('country', 'string', [
                'limit' => 100,
                'null' => true,
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
            ->addIndex(['name'], ['unique' => true])
            ->create();
    }
}
