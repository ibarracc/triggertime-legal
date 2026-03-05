<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'first_name' => true,
        'last_name' => true,
        'email' => true,
        'password_hash' => true,
        'role' => true,
        'stripe_customer_id' => true,
        'language' => true,
        'marketing_optin' => true,
        'email_verified_at' => true,
        'created_at' => true,
        'devices' => true,
        'subscriptions' => true,
        'activation_licenses' => true,
        'social_accounts' => true,
    ];

    protected array $_hidden = [
        'password_hash',
    ];

    /**
     * Password hash setter.
     *
     * @param string $password The password to hash.
     * @return string
     */
    protected function _setPasswordHash(string $password): ?string
    {
        if (strlen($password) > 0) {
            return password_hash($password, PASSWORD_DEFAULT);
        }

        return $password;
    }
}
