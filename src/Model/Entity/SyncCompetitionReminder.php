<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncCompetitionReminder extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'competition_uuid' => true,
        'reminder_offset' => true,
        'is_enabled' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'seq' => true,
        'version' => true,
        'modified' => true,
    ];
}
