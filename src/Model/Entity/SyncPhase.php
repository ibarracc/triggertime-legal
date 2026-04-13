<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncPhase extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'discipline_uuid' => true,
        'name' => true,
        'default_series_count' => true,
        'default_series_shots' => true,
        'default_series_total_shots' => true,
        'shot_timer_type' => true,
        'wait_seconds' => true,
        'seconds' => true,
        'active' => true,
        'allow_sighting_series' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
