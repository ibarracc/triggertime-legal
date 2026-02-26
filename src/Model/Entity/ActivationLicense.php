<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ActivationLicense extends Entity
{
    protected array $_accessible = [
        'app_instance' => true,
        'email' => true,
        'name' => true,
        'license_number' => true,
        'used' => true,
        'device_id' => true,
        'user_id' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'device' => true,
    ];
}
