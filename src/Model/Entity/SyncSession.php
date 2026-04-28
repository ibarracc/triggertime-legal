<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncSession extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'date' => true,
        'end_date' => true,
        'discipline_uuid' => true,
        'discipline_name' => true,
        'type' => true,
        'location' => true,
        'notes' => true,
        'total_score' => true,
        'total_x_count' => true,
        'event_uuid' => true,
        'category_uuid' => true,
        'scoring_type_id' => true,
        'auto_closed' => true,
        'weapon_uuid' => true,
        'ammo_uuid' => true,
        'competition_uuid' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'seq' => true,
        'version' => true,
        'sync_series' => true,
        'sync_strings' => true,
    ];
}
