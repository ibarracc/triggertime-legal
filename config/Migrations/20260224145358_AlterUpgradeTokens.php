<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AlterUpgradeTokens extends BaseMigration
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
        $table = $this->table('upgrade_tokens');

        // Change token to 36 chars to support UUID
        $table->changeColumn('token_string', 'string', [
            'limit' => 36,
            'null' => false,
        ]);

        // Add type column enum or simple string (upgrade, link)
        $table->addColumn('type', 'string', [
            'limit' => 20,
            'default' => 'upgrade',
            'null' => false,
        ]);

        $table->update();
    }
}
