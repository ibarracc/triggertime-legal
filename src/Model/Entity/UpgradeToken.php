<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class UpgradeToken extends Entity
{
    protected array $_accessible = [
        'token_string' => true,
        'type' => true,
        'device_uuid' => true,
        'expires_at' => true,
        'is_used' => true,
        'created' => true,
        'modified' => true,
    ];
}
