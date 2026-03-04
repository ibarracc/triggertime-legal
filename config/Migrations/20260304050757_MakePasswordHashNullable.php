<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MakePasswordHashNullable extends BaseMigration
{
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN natively.
            // We must recreate the table to change the NOT NULL constraint.
            $this->execute('PRAGMA foreign_keys = OFF');

            // Fetch current CREATE TABLE statement from sqlite_master
            $rows = $this->fetchAll(
                "SELECT * FROM sqlite_master WHERE \"type\" = 'table' AND \"tbl_name\" = 'users'"
            );

            if (empty($rows)) {
                // Table doesn't exist yet, nothing to alter
                $this->execute('PRAGMA foreign_keys = ON');
                return;
            }

            $createSql = $rows[0]['sql'];

            // Replace the NOT NULL constraint on password_hash
            // The column definition looks like: "password_hash" VARCHAR(255) NOT NULL
            $newCreateSql = preg_replace(
                '/("password_hash"\s+VARCHAR\(\d+\))\s+NOT\s+NULL/i',
                '$1 DEFAULT NULL',
                $createSql,
            );

            // Replace table name with tmp table name
            $tmpCreateSql = preg_replace(
                '/^CREATE\s+TABLE\s+[`"\[]?users[`"\]]?/i',
                'CREATE TABLE "tmp_users_pw_nullable"',
                $newCreateSql,
            );

            $this->execute($tmpCreateSql);

            // Get column list for INSERT...SELECT
            $columns = $this->fetchAll('PRAGMA table_info(users)');
            $columnNames = array_map(function ($col) {
                return '"' . $col['name'] . '"';
            }, $columns);
            $columnList = implode(', ', $columnNames);

            $this->execute("INSERT INTO \"tmp_users_pw_nullable\" ($columnList) SELECT $columnList FROM \"users\"");

            // Preserve indexes before dropping original table
            $indexes = $this->fetchAll(
                "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='users' AND sql IS NOT NULL"
            );

            $this->execute('DROP TABLE "users"');
            $this->execute('ALTER TABLE "tmp_users_pw_nullable" RENAME TO "users"');

            // Recreate indexes on the renamed table
            foreach ($indexes as $idx) {
                $this->execute($idx['sql']);
            }

            $this->execute('PRAGMA foreign_keys = ON');
        } else {
            $table = $this->table('users');
            $table->changeColumn('password_hash', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ]);
            $table->update();
        }
    }

    public function down(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'sqlite') {
            $this->execute('PRAGMA foreign_keys = OFF');

            $rows = $this->fetchAll(
                "SELECT * FROM sqlite_master WHERE \"type\" = 'table' AND \"tbl_name\" = 'users'"
            );

            if (empty($rows)) {
                $this->execute('PRAGMA foreign_keys = ON');
                return;
            }

            $createSql = $rows[0]['sql'];

            // Add NOT NULL constraint back to password_hash
            $newCreateSql = preg_replace(
                '/("password_hash"\s+VARCHAR\(\d+\))(\s+DEFAULT\s+NULL)?/i',
                '$1 NOT NULL',
                $createSql,
            );

            $tmpCreateSql = preg_replace(
                '/^CREATE\s+TABLE\s+[`"\[]?users[`"\]]?/i',
                'CREATE TABLE "tmp_users_pw_notnull"',
                $newCreateSql,
            );

            $this->execute($tmpCreateSql);

            $columns = $this->fetchAll('PRAGMA table_info(users)');
            $columnNames = array_map(function ($col) {
                return '"' . $col['name'] . '"';
            }, $columns);
            $columnList = implode(', ', $columnNames);

            $this->execute("INSERT INTO \"tmp_users_pw_notnull\" ($columnList) SELECT $columnList FROM \"users\"");

            // Preserve indexes before dropping original table
            $indexes = $this->fetchAll(
                "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='users' AND sql IS NOT NULL"
            );

            $this->execute('DROP TABLE "users"');
            $this->execute('ALTER TABLE "tmp_users_pw_notnull" RENAME TO "users"');

            // Recreate indexes on the renamed table
            foreach ($indexes as $idx) {
                $this->execute($idx['sql']);
            }

            $this->execute('PRAGMA foreign_keys = ON');
        } else {
            $table = $this->table('users');
            $table->changeColumn('password_hash', 'string', [
                'limit' => 255,
                'null' => false,
            ]);
            $table->update();
        }
    }
}
