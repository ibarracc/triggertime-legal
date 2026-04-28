<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class UserSyncSequence extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'current_seq' => true,
    ];
}
