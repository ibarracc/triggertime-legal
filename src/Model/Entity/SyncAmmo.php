<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncAmmo extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'brand' => true,
        'name' => true,
        'caliber' => true,
        'grain_weight' => true,
        'cost_per_round' => true,
        'current_stock' => true,
        'is_archived' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'seq' => true,
        'version' => true,
        'sync_ammo_transactions' => true,
    ];
}
