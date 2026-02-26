<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SubscriptionDevice extends Entity
{
    protected array $_accessible = [
        'subscription_id' => true,
        'device_id' => true,
        'created' => true,
        'modified' => true,
        'subscription' => true,
        'device' => true,
    ];
}
