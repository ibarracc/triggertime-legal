<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SocialAccount extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'provider' => true,
        'provider_uid' => true,
        'created_at' => true,
    ];
}
