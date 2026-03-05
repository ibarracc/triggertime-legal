<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddEmailVerifiedAtToUsers extends BaseMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'sqlite') {
            $rows = $this->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='users'",
            );

            if (!empty($rows)) {
                $this->execute(
                    'ALTER TABLE "users" ADD COLUMN "email_verified_at" TIMESTAMP DEFAULT NULL',
                );
                $this->execute(
                    'UPDATE "users" SET "email_verified_at" = "created_at" WHERE "email_verified_at" IS NULL',
                );
            }
        } else {
            $table = $this->table('users');
            $table->addColumn('email_verified_at', 'timestamp', [
                'default' => null,
                'null' => true,
                'after' => 'marketing_optin',
            ]);
            $table->update();

            // Backfill: treat all existing users as verified
            $this->execute('UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL');
        }
    }
}
