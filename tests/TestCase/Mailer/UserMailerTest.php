<?php
declare(strict_types=1);

namespace App\Test\TestCase\Mailer;

use App\Mailer\UserMailer;
use App\Model\Entity\User;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;

class UserMailerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => 'Debug']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TransportFactory::drop('default');
    }

    private function makeUser(array $overrides = []): User
    {
        $user = new User();
        $user->id = 'user-uuid-123';
        $user->email = 'test@example.com';
        $user->first_name = 'John';
        $user->language = 'en';
        foreach ($overrides as $key => $value) {
            $user->{$key} = $value;
        }

        return $user;
    }

    public function testWelcomeActivationSetsCorrectSubject(): void
    {
        $mailer = new UserMailer();
        $mailer->welcomeActivation($this->makeUser(), 'https://triggertime.es/verify-email?uid=x&exp=y&sig=z');

        $this->assertSame(['test@example.com' => 'test@example.com'], $mailer->getTo());
        $this->assertStringContainsString('TriggerTime', $mailer->getSubject());
    }

    public function testWelcomeSsoSetsCorrectSubject(): void
    {
        $mailer = new UserMailer();
        $mailer->welcomeSso($this->makeUser(), 'google');

        $this->assertSame(['test@example.com' => 'test@example.com'], $mailer->getTo());
        $this->assertStringContainsString('TriggerTime', $mailer->getSubject());
    }

    public function testPasswordResetSetsCorrectSubject(): void
    {
        $mailer = new UserMailer();
        $mailer->passwordReset($this->makeUser(), 'https://triggertime.es/reset-password/token123');

        $this->assertSame(['test@example.com' => 'test@example.com'], $mailer->getTo());
        $this->assertStringContainsString('TriggerTime', $mailer->getSubject());
    }
}
