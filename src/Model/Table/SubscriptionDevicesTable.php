<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;

class SubscriptionDevicesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('subscription_devices');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Subscriptions', [
            'foreignKey' => 'subscription_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Devices', [
            'foreignKey' => 'device_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('subscription_id')
            ->requirePresence('subscription_id', 'create')
            ->notEmptyString('subscription_id');

        $validator
            ->uuid('device_id')
            ->requirePresence('device_id', 'create')
            ->notEmptyString('device_id');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('subscription_id', 'Subscriptions'), ['errorField' => 'subscription_id']);
        $rules->add($rules->existsIn('device_id', 'Devices'), ['errorField' => 'device_id']);

        return $rules;
    }
}
