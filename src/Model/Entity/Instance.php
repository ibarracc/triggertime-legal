<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Instance Entity
 *
 * @property string $id
 * @property string $name
 * @property string|null $club_admin_id
 * @property bool $is_active
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\User $club_admin
 * @property \App\Model\Entity\ActivationLicense[] $activation_licenses
 * @property \App\Model\Entity\AppRemoteConfig[] $app_remote_config
 * @property \App\Model\Entity\Device[] $devices
 * @property \App\Model\Entity\Version[] $versions
 */
class Instance extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'club_admin_id' => true,
        'is_active' => true,
        'created' => true,
        'modified' => true,
        'club_admin' => true,
        'activation_licenses' => true,
        'app_remote_config' => true,
        'devices' => true,
        'versions' => true,
    ];
}
