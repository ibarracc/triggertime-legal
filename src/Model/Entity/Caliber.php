<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Caliber Entity
 *
 * @property int $id
 * @property string $name
 * @property string $weapon_category
 * @property string $standard
 * @property bool $is_active
 * @property int $sort_order
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Caliber extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'weapon_category' => true,
        'standard' => true,
        'is_active' => true,
        'sort_order' => true,
        'created' => true,
        'modified' => true,
    ];
}
