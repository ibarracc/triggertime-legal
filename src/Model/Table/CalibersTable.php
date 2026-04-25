<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Calibers Model
 *
 * @method \App\Model\Entity\Caliber newEmptyEntity()
 * @method \App\Model\Entity\Caliber newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Caliber> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Caliber get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Caliber findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Caliber patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Caliber> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Caliber|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Caliber saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Caliber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Caliber>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Caliber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Caliber> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Caliber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Caliber>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Caliber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Caliber> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CalibersTable extends Table
{
    /**
     * Valid weapon categories.
     *
     * @var array<string>
     */
    public const WEAPON_CATEGORIES = ['pistol', 'rifle', 'rimfire', 'shotshell'];

    /**
     * Valid standards.
     *
     * @var array<string>
     */
    public const STANDARDS = ['saami', 'cip'];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('calibers');
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
            ->scalar('weapon_category')
            ->maxLength('weapon_category', 50)
            ->requirePresence('weapon_category', 'create')
            ->notEmptyString('weapon_category')
            ->inList('weapon_category', self::WEAPON_CATEGORIES);

        $validator
            ->scalar('standard')
            ->maxLength('standard', 50)
            ->requirePresence('standard', 'create')
            ->notEmptyString('standard')
            ->inList('standard', self::STANDARDS);

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
        $rules->add(
            $rules->isUnique(
                ['name', 'weapon_category', 'standard'],
                'This caliber already exists for this category and standard.',
            ),
            ['errorField' => 'name'],
        );

        return $rules;
    }
}
