<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('password_hash', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('role', 'string', [
                'limit' => 20,
                'default' => 'user',
                'null' => true,
            ])
            ->addColumn('stripe_customer_id', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['stripe_customer_id'], ['unique' => true])
            ->create();
    }
}
