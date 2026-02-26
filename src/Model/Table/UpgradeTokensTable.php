<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UpgradeTokensTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('upgrade_tokens');
        $this->setDisplayField('token_string');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('token_string')
            ->maxLength('token_string', 255)
            ->requirePresence('token_string', 'create')
            ->notEmptyString('token_string');

        $validator
            ->scalar('device_uuid')
            ->maxLength('device_uuid', 255)
            ->requirePresence('device_uuid', 'create')
            ->notEmptyString('device_uuid');

        return $validator;
    }
}
