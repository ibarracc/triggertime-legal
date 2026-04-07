<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'c3792a3c-af61-479e-aaa3-16e763aacbf8',
                'email' => 'admin@example.com',
                'password_hash' => '$2y$10$72vI/zC71e8D7DrmXOTN6em/6W8k7cOoI6n3u2T4C2Tpw/s4kO53O',
                'role' => 'admin',
                'stripe_customer_id' => null,
                'marketing_optin' => false,
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
            [
                'id' => 'f2f2f2f2-a3a3-4b4b-8c5c-d6d6d6d6d6d2',
                'email' => 'user2@example.com',
                'password_hash' => '$2y$10$72vI/zC71e8D7DrmXOTN6em/6W8k7cOoI6n3u2T4C2Tpw/s4kO53O',
                'role' => 'user',
                'stripe_customer_id' => null,
                'marketing_optin' => false,
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
