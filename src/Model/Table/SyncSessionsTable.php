<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncSessionsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_sessions');
        $this->setDisplayField('discipline_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SyncSeries', [
            'foreignKey' => 'session_uuid',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->hasMany('SyncStrings', [
            'foreignKey' => 'session_uuid',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('discipline_name')
            ->maxLength('discipline_name', 255)
            ->requirePresence('discipline_name', 'create')
            ->notEmptyString('discipline_name');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
