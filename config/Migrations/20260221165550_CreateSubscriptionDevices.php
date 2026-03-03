<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSubscriptionDevices extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('subscription_devices', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true])
            ->addColumn('subscription_id', 'uuid', ['null' => false])
            ->addColumn('device_id', 'uuid', ['null' => false])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['subscription_id', 'device_id'], ['unique' => true])
            ->addIndex(['subscription_id'], ['name' => 'idx_sd_sub'])
            ->addIndex(['device_id'], ['name' => 'idx_sd_dev'])
            ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('device_id', 'devices', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
