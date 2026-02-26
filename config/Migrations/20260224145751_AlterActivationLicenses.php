<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AlterActivationLicenses extends BaseMigration
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
        $table = $this->table('activation_licenses');

        $table->removeColumn('device_identifier');
        $table->removeColumn('device_information');

        $table->addColumn('device_id', 'uuid', [
            'null' => true,
        ]);

        // Use constraint instead of simple index, or just foreign key.
        $table->addForeignKey('device_id', 'devices', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'NO_ACTION'
        ]);

        $table->update();
    }
}
