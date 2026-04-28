<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncString extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'session_uuid' => true,
        'phase_uuid' => true,
        'string_number_within_phase' => true,
        'total_score' => true,
        'x_count' => true,
        'first_miss' => true,
        'is_sighting' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'seq' => true,
        'version' => true,
        'modified' => true,
    ];
}
