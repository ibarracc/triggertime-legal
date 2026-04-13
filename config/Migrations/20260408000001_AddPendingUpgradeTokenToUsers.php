<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPendingUpgradeTokenToUsers extends BaseMigration
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
        if ($this->getAdapter()->getAdapterType() === 'sqlite') {
            $rows = $this->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='users'",
            );

            if (!empty($rows)) {
                $this->execute(
                    'ALTER TABLE "users" ADD COLUMN "pending_upgrade_token" VARCHAR(255) DEFAULT NULL',
                );
            }
        } else {
            $table = $this->table('users');
            $table->addColumn('pending_upgrade_token', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ]);
            $table->update();
        }
    }
}
