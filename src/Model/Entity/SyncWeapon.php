<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncWeapon extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'name' => true,
        'caliber' => true,
        'serial_number' => true,
        'notes' => true,
        'is_favorite' => true,
        'is_archived' => true,
        'shot_count' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'seq' => true,
        'version' => true,
        'modified' => true,
    ];
}
