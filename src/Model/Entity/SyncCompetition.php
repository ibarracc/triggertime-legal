<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncCompetition extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'name' => true,
        'date' => true,
        'end_date' => true,
        'location' => true,
        'discipline_id' => true,
        'status' => true,
        'notes' => true,
        'is_active' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'seq' => true,
        'version' => true,
        'sync_competition_reminders' => true,
    ];
}
