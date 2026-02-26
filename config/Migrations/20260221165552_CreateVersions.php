<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateVersions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('versions', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true])
            ->addColumn('app_instance', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('version', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('disabled', 'boolean', ['default' => false, 'null' => true])
            ->addColumn('created', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => true,
            ])
            ->addIndex(['app_instance', 'version'], ['unique' => true])
            ->create();
    }
}
