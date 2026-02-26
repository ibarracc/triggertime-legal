<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePasswordResets extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('password_resets', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('user_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('token', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('expires_at', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('used', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addIndex(['token'], ['unique' => true]);
        $table->create();
    }
}
