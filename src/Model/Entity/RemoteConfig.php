<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class RemoteConfig extends Entity
{
    protected array $_accessible = [
        'app_instance' => true,
        'instance_id' => true,
        'version_id' => true,
        'config_data' => true,
        'created' => true,
        'modified' => true,
    ];
}
