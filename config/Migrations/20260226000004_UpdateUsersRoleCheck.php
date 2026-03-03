<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class UpdateUsersRoleCheck extends BaseMigration
{
    public function up(): void
    {
        // Try to drop the existing check constraint and add a new one that allows 'club_admin'
        try {
            $this->execute("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            $this->execute("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user', 'admin', 'club_admin'))");
        } catch (\Exception $e) {
            // Ignore error if constraint does not exist
        }
    }

    public function down(): void
    {
        try {
            $this->execute("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            $this->execute("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user', 'admin'))");
        } catch (\Exception $e) {
            // Ignore error
        }
    }
}
