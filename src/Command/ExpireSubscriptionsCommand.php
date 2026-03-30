<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Exception;

class ExpireSubscriptionsCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Mark subscriptions as canceled when cancel_at_period_end is true and current_period_end has passed.',
        );
        $parser->addOption('dry-run', [
            'help' => 'Show what would be expired without actually updating',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $dryRun = (bool)$args->getOption('dry-run');
        $now = DateTime::now();

        $subscriptionsTable = $this->fetchTable('Subscriptions');

        $expiredSubs = $subscriptionsTable->find()
            ->select(['id', 'user_id', 'status', 'current_period_end'])
            ->where([
                'status' => 'active',
                'cancel_at_period_end' => true,
                'current_period_end <' => $now,
            ])
            ->disableResultsCasting()
            ->all();

        $count = $expiredSubs->count();

        if ($count === 0) {
            $io->out('No expired subscriptions found.');

            return static::CODE_SUCCESS;
        }

        $io->out(sprintf('Found %d subscription(s) to expire.', $count));

        if ($dryRun) {
            foreach ($expiredSubs as $sub) {
                $io->out(sprintf(
                    '  [DRY RUN] Would expire subscription %s (user: %s, ended: %s)',
                    $sub['id'],
                    $sub['user_id'],
                    $sub['current_period_end'],
                ));
            }

            return static::CODE_SUCCESS;
        }

        $expired = 0;
        foreach ($expiredSubs as $sub) {
            try {
                $subscriptionsTable->getConnection()
                    ->update('subscriptions', ['status' => 'canceled'], ['id' => $sub['id']]);
                $expired++;
                $io->out(sprintf('  Expired subscription %s (user: %s)', $sub['id'], $sub['user_id']));
                Log::info(sprintf('Expired subscription %s for user %s', $sub['id'], $sub['user_id']));
            } catch (Exception $e) {
                $io->err(sprintf('  Failed to expire subscription %s: %s', $sub['id'], $e->getMessage()));
                Log::error(sprintf('Failed to expire subscription %s: %s', $sub['id'], $e->getMessage()));
            }
        }

        $io->out(sprintf('Expired %d/%d subscriptions.', $expired, $count));

        return static::CODE_SUCCESS;
    }
}
