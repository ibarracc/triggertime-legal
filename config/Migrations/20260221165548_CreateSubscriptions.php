<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSubscriptions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('subscriptions', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', [
                'null' => false,
            ])
            ->addColumn('stripe_subscription_id', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('plan', 'string', [
                'limit' => 20,
                'default' => 'free',
                'null' => true,
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'active',
                'null' => true,
            ])
            ->addColumn('max_devices_allowed', 'integer', [
                'default' => 2,
                'null' => true,
            ])
            ->addColumn('valid_until', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['stripe_subscription_id'], ['unique' => true])
            ->addIndex(['user_id'], ['name' => 'idx_sub_user'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
