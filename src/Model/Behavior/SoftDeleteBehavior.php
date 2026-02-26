<?php

declare(strict_types=1);

namespace App\Model\Behavior;

use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Datasource\EntityInterface;
use ArrayObject;

class SoftDeleteBehavior extends Behavior
{
    protected array $_defaultConfig = [
        'field' => 'deleted_at',
    ];

    /**
     * Intercept find queries to exclude soft-deleted records.
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, $primary): void
    {
        // Check if we specifically want to include deleted records
        if (isset($options['withDeleted']) && $options['withDeleted'] === true) {
            return;
        }

        // Apply where deleted_at IS NULL
        $field = $this->getConfig('field');
        $alias = $this->_table->getAlias();

        // This targets the primary alias of the table
        $query->where(["$alias.$field IS" => null]);
    }

    /**
     * Intercept deletes to perform an update instead.
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): bool
    {
        // If hardDelete is explicitly passed in options, allow the actual delete
        if (isset($options['hardDelete']) && $options['hardDelete'] === true) {
            return true;
        }

        $field = $this->getConfig('field');

        $entity->set($field, date('Y-m-d H:i:s'));
        $this->_table->save($entity, [
            'callbacks' => false,
            // Skip validation on a soft delete since the record might be invalid
            // but we just want to mark it deleted
            'validate' => false,
        ]);

        // Stop the physical deletion process
        $event->stopPropagation();

        return true;
    }

    /**
     * Restore a soft-deleted record.
     */
    public function restore(EntityInterface $entity): bool
    {
        $field = $this->getConfig('field');
        $entity->set($field, null);
        return (bool)$this->_table->save($entity);
    }
}
