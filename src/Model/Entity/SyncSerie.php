<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncSerie extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'session_uuid' => true,
        'phase_uuid' => true,
        'series_number_within_phase' => true,
        'total_score' => true,
        'total_x_count' => true,
        'is_sighting' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'seq' => true,
        'version' => true,
        'sync_shots' => true,
    ];
}
