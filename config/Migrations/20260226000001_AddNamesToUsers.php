<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddNamesToUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('first_name', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => true,
            'after' => 'email',
        ]);
        $table->addColumn('last_name', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => true,
            'after' => 'first_name',
        ]);
        $table->update();
    }
}
