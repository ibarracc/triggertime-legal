<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Instances Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $ClubAdmins
 * @property \App\Model\Table\ActivationLicensesTable&\Cake\ORM\Association\HasMany $ActivationLicenses
 * @property \App\Model\Table\AppRemoteConfigTable&\Cake\ORM\Association\HasMany $AppRemoteConfig
 * @property \App\Model\Table\DevicesTable&\Cake\ORM\Association\HasMany $Devices
 * @property \App\Model\Table\VersionsTable&\Cake\ORM\Association\HasMany $Versions
 *
 * @method \App\Model\Entity\Instance newEmptyEntity()
 * @method \App\Model\Entity\Instance newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Instance> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Instance get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Instance findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Instance patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Instance> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Instance|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Instance saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Instance>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Instance>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Instance>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Instance> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Instance>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Instance>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Instance>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Instance> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class InstancesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('instances');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('SoftDelete');

        $this->belongsTo('ClubAdmins', [
            'foreignKey' => 'club_admin_id',
            'className' => 'Users',
        ]);
        $this->hasMany('ActivationLicenses', [
            'foreignKey' => 'instance_id',
        ]);
        $this->hasMany('AppRemoteConfig', [
            'foreignKey' => 'instance_id',
        ]);
        $this->hasMany('Devices', [
            'foreignKey' => 'instance_id',
        ]);
        $this->hasMany('Versions', [
            'foreignKey' => 'instance_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->uuid('club_admin_id')
            ->allowEmptyString('club_admin_id');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['name']), ['errorField' => 'name']);
        $rules->add($rules->existsIn(['club_admin_id'], 'ClubAdmins'), ['errorField' => 'club_admin_id']);

        return $rules;
    }
}
