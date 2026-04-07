<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncDiscipline extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'name' => true,
        'weapon_type_id' => true,
        'scoring_type_id' => true,
        'use_fm' => true,
        'active' => true,
        'show_previous_series_on_scoring' => true,
        'max_score_per_shot' => true,
        'x_label' => true,
        'always_editable_series' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'sync_phases' => true,
    ];
}
