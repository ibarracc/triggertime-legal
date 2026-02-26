<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddConfigDataToAppRemoteConfig extends BaseMigration
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
        $table = $this->table('app_remote_config');
        $table->addColumn('config_data', 'text', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
