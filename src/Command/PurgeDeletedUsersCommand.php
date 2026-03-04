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

class PurgeDeletedUsersCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Permanently delete users that were soft-deleted more than 30 days ago.');
        $parser->addOption('dry-run', [
            'help' => 'Show what would be deleted without actually deleting',
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
        $cutoff = DateTime::now()->subDays(30);

        $usersTable = $this->fetchTable('Users');

        // Bypass SoftDelete to find soft-deleted users
        $users = $usersTable->find('all', withDeleted: true)
            ->select(['id', 'email', 'deleted_at'])
            ->where([
                'deleted_at IS NOT' => null,
                'deleted_at <=' => $cutoff,
            ])
            ->disableResultsCasting()
            ->all();

        $count = $users->count();

        if ($count === 0) {
            $io->out('No users to purge.');

            return static::CODE_SUCCESS;
        }

        $io->out(sprintf('Found %d user(s) to purge (deleted before %s).', $count, $cutoff->format('Y-m-d H:i:s')));

        if ($dryRun) {
            foreach ($users as $user) {
                $io->out(sprintf('  [DRY RUN] Would purge user %s (%s)', $user['id'], $user['email']));
            }

            return static::CODE_SUCCESS;
        }

        $connection = $usersTable->getConnection();
        $purged = 0;

        foreach ($users as $user) {
            try {
                $connection->delete('social_accounts', ['user_id' => $user['id']]);
                $connection->delete('devices', ['user_id' => $user['id']]);
                $connection->delete('subscriptions', ['user_id' => $user['id']]);
                $connection->delete('activation_licenses', ['user_id' => $user['id']]);
                $connection->delete('users', ['id' => $user['id']]);
                $purged++;
                $io->out(sprintf('  Purged user %s (%s)', $user['id'], $user['email']));
                Log::info(sprintf('Purged deleted user %s (%s)', $user['id'], $user['email']));
            } catch (Exception $e) {
                $io->err(sprintf('  Failed to purge user %s: %s', $user['id'], $e->getMessage()));
                Log::error(sprintf('Failed to purge user %s: %s', $user['id'], $e->getMessage()));
            }
        }

        $io->out(sprintf('Purged %d/%d users.', $purged, $count));

        return static::CODE_SUCCESS;
    }
}
