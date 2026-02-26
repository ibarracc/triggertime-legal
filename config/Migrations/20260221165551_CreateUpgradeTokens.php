<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateUpgradeTokens extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('upgrade_tokens', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('token_string', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('device_uuid', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('expires_at', 'timestamp', [
                'null' => false,
            ])
            ->addColumn('is_used', 'boolean', [
                'default' => false,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['token_string'], ['unique' => true])
            ->addIndex(['token_string', 'expires_at'], ['name' => 'idx_token_exp'])
            ->create();
    }
}
