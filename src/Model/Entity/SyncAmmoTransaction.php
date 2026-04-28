<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncAmmoTransaction extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'ammo_uuid' => true,
        'type' => true,
        'quantity' => true,
        'session_uuid' => true,
        'weapon_uuid' => true,
        'notes' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'seq' => true,
        'version' => true,
        'modified' => true,
    ];
}
