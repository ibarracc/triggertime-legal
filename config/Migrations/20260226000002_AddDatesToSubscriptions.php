<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddDatesToSubscriptions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('subscriptions');
        $table->addColumn('current_period_start', 'timestamp', [
            'default' => null,
            'null' => true,
            'after' => 'max_devices_allowed',
        ]);
        $table->addColumn('current_period_end', 'timestamp', [
            'default' => null,
            'null' => true,
            'after' => 'current_period_start',
        ]);
        $table->update();
    }
}
