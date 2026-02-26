<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class RemoteConfigTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('app_remote_config');
        $this->setDisplayField('app_instance');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Instances', [
            'foreignKey' => 'instance_id',
        ]);

        $this->belongsTo('Versions', [
            'foreignKey' => 'version_id',
        ]);
    }
}
