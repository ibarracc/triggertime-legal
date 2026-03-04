<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSocialAccounts extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('social_accounts', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('provider', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('provider_uid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => true])
            ->addIndex(['provider', 'provider_uid'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
