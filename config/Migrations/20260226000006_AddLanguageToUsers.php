<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddLanguageToUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('language', 'string', [
            'default' => 'en',
            'limit' => 5,
            'null' => false,
            'after' => 'last_name',
        ]);
        $table->update();
    }
}
