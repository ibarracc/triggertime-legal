<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ActivationLicensesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('activation_licenses');
        $this->setDisplayField('license_number');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('SoftDelete');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);

        $this->belongsTo('Devices', [
            'foreignKey' => 'device_id',
        ]);
        $this->belongsTo('Instances', [
            'foreignKey' => 'instance_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('instance_id', 'create')
            ->notEmptyString('instance_id');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->requirePresence('license_number', 'create')
            ->notEmptyString('license_number')
            ->add('license_number', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        return $validator;
    }
}
