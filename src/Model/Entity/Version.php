<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Version extends Entity
{
    protected array $_accessible = [
        'instance_id' => true,
        'version' => true,
        'disabled' => true,
        'created' => true,
        'modified' => true,
    ];
}
