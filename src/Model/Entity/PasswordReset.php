<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PasswordReset extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'token' => true,
        'expires_at' => true,
        'used' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
    ];
}
