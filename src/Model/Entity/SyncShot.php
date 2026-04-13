<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncShot extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'series_uuid' => true,
        'value' => true,
        'is_x' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
