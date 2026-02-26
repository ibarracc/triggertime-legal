<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class RefactorAppInstances extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function up(): void
    {
        $tables = ['devices', 'activation_licenses', 'versions', 'app_remote_config'];

        // 1. Add instance_id column
        foreach ($tables as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('instance_id', 'uuid', ['null' => true, 'after' => 'app_instance'])
                ->update();
        }

        // 2. Migrate Data
        // Find all unique app_instances
        $uniqueInstances = [];
        foreach ($tables as $tableName) {
            $rows = $this->fetchAll("SELECT DISTINCT app_instance FROM $tableName WHERE app_instance IS NOT NULL");
            foreach ($rows as $row) {
                if (!empty($row['app_instance'])) {
                    $uniqueInstances[$row['app_instance']] = true;
                }
            }
        }

        $instanceMap = [];
        foreach (array_keys($uniqueInstances) as $appName) {
            $appNameSafe = addslashes($appName);
            // Check if instance already exists
            $existing = $this->fetchRow("SELECT id FROM instances WHERE name = '$appNameSafe'");
            if ($existing) {
                $uuid = $existing['id'];
            } else {
                $uuid = \Cake\Utility\Text::uuid();
                $this->execute(sprintf(
                    "INSERT INTO instances (id, name, is_active, created, modified) VALUES ('%s', '%s', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    $uuid,
                    $appNameSafe
                ));
            }
            $instanceMap[$appName] = $uuid;
        }

        // 3. Update tables with new instance_id
        foreach ($tables as $tableName) {
            foreach ($instanceMap as $appName => $uuid) {
                $appNameSafe = addslashes($appName);
                $this->execute(sprintf(
                    "UPDATE %s SET instance_id = '%s' WHERE app_instance = '%s'",
                    $tableName,
                    $uuid,
                    $appNameSafe
                ));
            }
        }

        // 4. Handle indexes and drop app_instance

        // activation_licenses
        $licTable = $this->table('activation_licenses');
        if ($licTable->hasIndex(['email', 'app_instance'])) {
            $licTable->removeIndex(['email', 'app_instance']);
        }
        if ($licTable->hasIndex(['app_instance'])) {
            $licTable->removeIndex(['app_instance']);
        }
        $licTable->update();

        $licTable->addIndex(['email', 'instance_id'], ['unique' => true, 'name' => 'licenses_email_instance_id'])
            ->addIndex(['instance_id'], ['name' => 'idx_lic_inst'])
            ->removeColumn('app_instance')
            ->addForeignKey('instance_id', 'instances', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->update();

        // versions
        $verTable = $this->table('versions');
        if ($verTable->hasIndex(['app_instance', 'version'])) {
            $verTable->removeIndex(['app_instance', 'version']);
        }
        $verTable->update();

        $verTable->addIndex(['instance_id', 'version'], ['unique' => true, 'name' => 'idx_app_ver_inst'])
            ->removeColumn('app_instance')
            ->addForeignKey('instance_id', 'instances', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->update();

        // app_remote_config
        $cfgTable = $this->table('app_remote_config');
        if ($cfgTable->hasIndex(['app_instance', 'version_id'])) {
            $cfgTable->removeIndex(['app_instance', 'version_id']);
        }
        $cfgTable->update();

        $cfgTable->addIndex(['instance_id', 'version_id'], ['unique' => true, 'name' => 'idx_app_cfg_inst'])
            ->removeColumn('app_instance')
            ->addForeignKey('instance_id', 'instances', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->update();

        // devices
        $devTable = $this->table('devices');
        $devTable->removeColumn('app_instance')
            ->addForeignKey('instance_id', 'instances', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->update();
    }

    public function down(): void
    {
        // Reversal logic would require reading the `instances` table and re-hydrating the `app_instance` column.
    }
}
