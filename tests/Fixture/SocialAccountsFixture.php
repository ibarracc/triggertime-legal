<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SocialAccountsFixture extends TestFixture
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
                'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                'user_id' => 'c3792a3c-af61-479e-aaa3-16e763aacbf8',
                'provider' => 'google',
                'provider_uid' => 'google-uid-123456',
                'created_at' => '2026-03-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
