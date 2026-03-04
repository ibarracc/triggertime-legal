<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddMarketingOptinToUsers extends BaseMigration
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
        if ($this->getAdapter()->getAdapterType() === 'sqlite') {
            // Check if the table exists (may have been dropped by MakePasswordHashNullable)
            $rows = $this->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='users'"
            );

            if (!empty($rows)) {
                $this->execute(
                    'ALTER TABLE "users" ADD COLUMN "marketing_optin" BOOLEAN NOT NULL DEFAULT 0'
                );
            }
        } else {
            $table = $this->table('users');
            $table->addColumn('marketing_optin', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'language',
            ])->update();
        }
    }
}
