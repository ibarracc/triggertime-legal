<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveValidUntilFromSubscriptions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('subscriptions');
        $table->removeColumn('valid_until');
        $table->update();
    }
}
