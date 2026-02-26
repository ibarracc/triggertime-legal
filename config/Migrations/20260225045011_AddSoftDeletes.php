<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddSoftDeletes extends BaseMigration
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
        $tables = [
            'users',
            'instances',
            'devices',
            'activation_licenses',
            'subscriptions'
        ];

        foreach ($tables as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('deleted_at', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ]);
            $table->update();
        }
    }
}
