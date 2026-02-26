<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Subscription extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'stripe_subscription_id' => true,
        'plan' => true,
        'status' => true,
        'max_devices_allowed' => true,
        'current_period_start' => true,
        'current_period_end' => true,
        'cancel_at_period_end' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'devices' => true,
    ];
}
