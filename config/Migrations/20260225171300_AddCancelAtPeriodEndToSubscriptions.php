<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddCancelAtPeriodEndToSubscriptions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('subscriptions');
        $table->addColumn('cancel_at_period_end', 'boolean', [
            'default' => false,
            'null' => false,
            'after' => 'current_period_end',
        ]);
        $table->update();
    }
}
