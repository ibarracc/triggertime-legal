<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveShowUpgradeButtonFromAppRemoteConfig extends BaseMigration
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
        if ($table->hasColumn('show_upgrade_button')) {
            $table->removeColumn('show_upgrade_button');
        }
        $table->update();
    }
}
