<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                ]
            ]
        ]);
        $this->addBehavior('SoftDelete');

        $this->hasMany('Devices', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('Subscriptions', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('ActivationLicenses', [
            'foreignKey' => 'user_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->requirePresence('password_hash', 'create')
            ->notEmptyString('password_hash');

        $validator
            ->allowEmptyString('language')
            ->maxLength('language', 5);

        return $validator;
    }
}
