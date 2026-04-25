<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Brands Model
 *
 * @method \App\Model\Entity\Brand newEmptyEntity()
 * @method \App\Model\Entity\Brand newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Brand> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Brand get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Brand findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Brand patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Brand> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Brand|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Brand saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Brand>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Brand>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Brand>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Brand> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Brand>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Brand>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Brand>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Brand> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BrandsTable extends Table
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

        $this->setTable('brands');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->notEmptyString('name');

        $validator
            ->scalar('country')
            ->maxLength('country', 100)
            ->allowEmptyString('country');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

        $validator
            ->integer('sort_order')
            ->notEmptyString('sort_order');

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

        return $rules;
    }
}
