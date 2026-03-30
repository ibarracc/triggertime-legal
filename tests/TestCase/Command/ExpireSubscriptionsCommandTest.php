<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\ExpireSubscriptionsCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class ExpireSubscriptionsCommandTest extends TestCase
{
    protected array $fixtures = [
        'app.Subscriptions',
        'app.SubscriptionDevices',
        'app.Users',
    ];

    /**
     * Test that subscriptions past their period end with cancel_at_period_end are expired.
     */
    public function testExpiresOverdueSubscriptions(): void
    {
        $subscriptions = $this->fetchTable('Subscriptions');
        $users = $this->fetchTable('Users');

        $user = $users->newEntity([
            'email' => 'expire-test@example.com',
            'password_hash' => 'hashed',
            'role' => 'user',
        ]);
        $users->saveOrFail($user);

        $sub = $subscriptions->newEntity([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_expired_test',
            'plan' => 'pro',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'current_period_start' => DateTime::now()->subDays(35),
            'current_period_end' => DateTime::now()->subDays(5),
            'max_devices_allowed' => 5,
        ]);
        $subscriptions->saveOrFail($sub);

        $result = $this->runCommand([]);

        $this->assertSame(ExpireSubscriptionsCommand::CODE_SUCCESS, $result);

        $updated = $subscriptions->get($sub->id);
        $this->assertSame('canceled', $updated->status);
    }

    /**
     * Test that active subscriptions not scheduled to cancel are left alone.
     */
    public function testLeavesActiveSubscriptionsAlone(): void
    {
        $subscriptions = $this->fetchTable('Subscriptions');
        $users = $this->fetchTable('Users');

        $user = $users->newEntity([
            'email' => 'active-test@example.com',
            'password_hash' => 'hashed',
            'role' => 'user',
        ]);
        $users->saveOrFail($user);

        $sub = $subscriptions->newEntity([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_active_test',
            'plan' => 'pro',
            'status' => 'active',
            'cancel_at_period_end' => false,
            'current_period_start' => DateTime::now()->subDays(5),
            'current_period_end' => DateTime::now()->addDays(25),
            'max_devices_allowed' => 5,
        ]);
        $subscriptions->saveOrFail($sub);

        $result = $this->runCommand([]);

        $this->assertSame(ExpireSubscriptionsCommand::CODE_SUCCESS, $result);

        $updated = $subscriptions->get($sub->id);
        $this->assertSame('active', $updated->status);
    }

    /**
     * Test dry-run mode does not update records.
     */
    public function testDryRunDoesNotUpdate(): void
    {
        $subscriptions = $this->fetchTable('Subscriptions');
        $users = $this->fetchTable('Users');

        $user = $users->newEntity([
            'email' => 'dryrun-test@example.com',
            'password_hash' => 'hashed',
            'role' => 'user',
        ]);
        $users->saveOrFail($user);

        $sub = $subscriptions->newEntity([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_dryrun_test',
            'plan' => 'pro',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'current_period_start' => DateTime::now()->subDays(35),
            'current_period_end' => DateTime::now()->subDays(5),
            'max_devices_allowed' => 5,
        ]);
        $subscriptions->saveOrFail($sub);

        $result = $this->runCommand(['--dry-run']);

        $this->assertSame(ExpireSubscriptionsCommand::CODE_SUCCESS, $result);

        $updated = $subscriptions->get($sub->id);
        $this->assertSame('active', $updated->status);
    }

    /**
     * Test that subscriptions with cancel_at_period_end but future end date are not expired.
     */
    public function testDoesNotExpireFutureCancellations(): void
    {
        $subscriptions = $this->fetchTable('Subscriptions');
        $users = $this->fetchTable('Users');

        $user = $users->newEntity([
            'email' => 'future-test@example.com',
            'password_hash' => 'hashed',
            'role' => 'user',
        ]);
        $users->saveOrFail($user);

        $sub = $subscriptions->newEntity([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_future_test',
            'plan' => 'pro',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'current_period_start' => DateTime::now()->subDays(5),
            'current_period_end' => DateTime::now()->addDays(25),
            'max_devices_allowed' => 5,
        ]);
        $subscriptions->saveOrFail($sub);

        $result = $this->runCommand([]);

        $this->assertSame(ExpireSubscriptionsCommand::CODE_SUCCESS, $result);

        $updated = $subscriptions->get($sub->id);
        $this->assertSame('active', $updated->status);
    }

    private function runCommand(array $args): int
    {
        $command = new ExpireSubscriptionsCommand();
        $out = new StubConsoleOutput();
        $err = new StubConsoleOutput();
        $io = new ConsoleIo($out, $err);

        $parser = $command->getOptionParser();
        [$parsedOptions, $parsedArgsList] = $parser->parse($args);

        $parsedArgs = new Arguments(
            $parsedArgsList,
            $parsedOptions,
            $parser->arguments(),
        );

        return $command->execute($parsedArgs, $io);
    }
}
