<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class VersionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('versions');
        $this->setDisplayField('version');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Instances', [
            'foreignKey' => 'instance_id',
        ]);
    }
}
