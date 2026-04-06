<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Fix SQLite foreign key and auto-increment constraints.
 *
 * SQLite requires the referenced column in a FK relationship to have either
 * an INTEGER PRIMARY KEY or a UNIQUE constraint. When using UUID primary keys
 * with phinx/cakephp-migrations on SQLite, the PRIMARY KEY clause is not always
 * emitted in the DDL, so FK references fail with "foreign key mismatch".
 *
 * Additionally, BIGINT auto-increment columns in SQLite need to be declared as
 * INTEGER PRIMARY KEY to work correctly.
 *
 * These fixes only apply to SQLite (test environment); MySQL handles these correctly.
 */
class AddUniqueIndexToInstancesId extends BaseMigration
{
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'sqlite') {
            return;
        }

        $this->execute('PRAGMA foreign_keys = OFF');

        // 1. Add unique index on instances.id so FK references to it work in SQLite
        $instances = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='instances'");
        if (!empty($instances)) {
            $this->execute('CREATE UNIQUE INDEX IF NOT EXISTS "instances_id_unique" ON "instances" ("id" ASC)');
        }

        // 2. Add unique index on versions.id so FK references to it work in SQLite
        $versions = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='versions'");
        if (!empty($versions)) {
            $this->execute('CREATE UNIQUE INDEX IF NOT EXISTS "versions_id_unique" ON "versions" ("id" ASC)');
        }

        // 3. Recreate app_remote_config with INTEGER PRIMARY KEY for proper auto-increment in SQLite
        $arcRows = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='app_remote_config'");
        if (!empty($arcRows)) {
            $this->execute(
                'CREATE TABLE IF NOT EXISTS "tmp_app_remote_config" (' .
                '"id" INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                '"version_id" BIGINT, ' .
                '"created" TIMESTAMP DEFAULT NULL, ' .
                '"modified" TIMESTAMP DEFAULT NULL, ' .
                '"instance_id" CHAR(36), ' .
                '"config_data" TEXT, ' .
                'FOREIGN KEY ("version_id") REFERENCES "versions" ("id") ON DELETE SET NULL ON UPDATE NO ACTION, ' .
                'FOREIGN KEY ("instance_id") REFERENCES "instances" ("id") ON DELETE CASCADE ON UPDATE CASCADE' .
                ')'
            );

            $cols = $this->fetchAll('PRAGMA table_info(app_remote_config)');
            $colNames = array_map(fn($c) => '"' . $c['name'] . '"', $cols);
            $colList = implode(', ', $colNames);
            $this->execute("INSERT INTO \"tmp_app_remote_config\" ($colList) SELECT $colList FROM \"app_remote_config\"");

            $indexes = $this->fetchAll(
                "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='app_remote_config' AND sql IS NOT NULL"
            );

            $this->execute('DROP TABLE "app_remote_config"');
            $this->execute('ALTER TABLE "tmp_app_remote_config" RENAME TO "app_remote_config"');

            foreach ($indexes as $idx) {
                $this->execute($idx['sql']);
            }
        }

        $this->execute('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'sqlite') {
            return;
        }

        // Remove unique indexes on id columns
        $this->execute('DROP INDEX IF EXISTS "instances_id_unique"');
        $this->execute('DROP INDEX IF EXISTS "versions_id_unique"');
    }
}
