<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Device extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'device_uuid' => true,
        'custom_name' => true,
        'hardware_model' => true,
        'app_instance' => true,
        'platform' => true,
        'os_version' => true,
        'app_version' => true,
        'first_activation_date' => true,
        'user_id' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'subscriptions' => true,
    ];
}
