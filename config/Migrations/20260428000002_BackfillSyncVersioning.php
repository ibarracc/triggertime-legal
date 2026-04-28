<?php
// config/Migrations/20260428000002_BackfillSyncVersioning.php
declare(strict_types=1);

use Migrations\BaseMigration;

class BackfillSyncVersioning extends BaseMigration
{
    public function up(): void
    {
        $syncTables = [
            'sync_disciplines',
            'sync_phases',
            'sync_sessions',
            'sync_series',
            'sync_shots',
            'sync_strings',
            'sync_weapons',
            'sync_ammo',
            'sync_competitions',
            'sync_competition_reminders',
            'sync_ammo_transactions',
        ];

        $directOwnership = [
            'sync_disciplines',
            'sync_sessions',
            'sync_weapons',
            'sync_ammo',
            'sync_competitions',
        ];

        $userIds = $this->fetchAll('SELECT DISTINCT id FROM users');

        foreach ($userIds as $userRow) {
            $userId = $userRow['id'];
            $seq = 0;

            $allRecords = [];
            foreach ($syncTables as $tableName) {
                if (in_array($tableName, $directOwnership)) {
                    $records = $this->fetchAll(
                        "SELECT id, modified_at FROM {$tableName} WHERE user_id = '{$userId}' ORDER BY modified_at ASC",
                    );
                } else {
                    $records = $this->fetchAll(
                        "SELECT id, modified_at FROM {$tableName} ORDER BY modified_at ASC",
                    );
                }

                foreach ($records as $record) {
                    $allRecords[] = [
                        'table' => $tableName,
                        'id' => $record['id'],
                        'modified_at' => $record['modified_at'],
                    ];
                }
            }

            usort($allRecords, function ($a, $b) {
                return strcmp((string)$a['modified_at'], (string)$b['modified_at']);
            });

            foreach ($allRecords as $record) {
                $seq++;
                $this->execute(
                    "UPDATE {$record['table']} SET seq = {$seq}, version = 1 WHERE id = '{$record['id']}'",
                );
            }

            if ($seq > 0) {
                $existing = $this->fetchAll(
                    "SELECT user_id FROM user_sync_sequences WHERE user_id = '{$userId}'",
                );
                if (empty($existing)) {
                    $this->execute(
                        "INSERT INTO user_sync_sequences (user_id, current_seq) VALUES ('{$userId}', {$seq})",
                    );
                } else {
                    $this->execute(
                        "UPDATE user_sync_sequences SET current_seq = {$seq} WHERE user_id = '{$userId}'",
                    );
                }
            }
        }

        // Backfill ammo adjustment transactions for ammo records missing transaction history
        $ammoRecords = $this->fetchAll(
            'SELECT sa.id, sa.user_id, sa.device_uuid, sa.current_stock, sa.modified_at, ' .
            'COALESCE((SELECT SUM(sat.quantity) FROM sync_ammo_transactions sat WHERE sat.ammo_uuid = sa.id AND sat.deleted_at IS NULL), 0) as tx_sum ' .
            'FROM sync_ammo sa WHERE sa.deleted_at IS NULL',
        );

        foreach ($ammoRecords as $ammo) {
            $diff = (int)$ammo['current_stock'] - (int)$ammo['tx_sum'];
            if ($diff !== 0) {
                $txUuid = $this->generateUuid();
                $userId = $ammo['user_id'];

                $existingSeq = $this->fetchAll(
                    "SELECT current_seq FROM user_sync_sequences WHERE user_id = '{$userId}'",
                );
                $currentSeq = !empty($existingSeq) ? (int)$existingSeq[0]['current_seq'] : 0;
                $newSeq = $currentSeq + 1;

                $this->execute(
                    "INSERT INTO sync_ammo_transactions (id, ammo_uuid, type, quantity, notes, modified_at, created, modified, seq, version) " .
                    "VALUES ('{$txUuid}', '{$ammo['id']}', 'adjustment', {$diff}, 'Initial balance from migration', '{$ammo['modified_at']}', NOW(), NOW(), {$newSeq}, 1)",
                );

                if ($currentSeq === 0 && empty($existingSeq)) {
                    $this->execute(
                        "INSERT INTO user_sync_sequences (user_id, current_seq) VALUES ('{$userId}', {$newSeq})",
                    );
                } else {
                    $this->execute(
                        "UPDATE user_sync_sequences SET current_seq = {$newSeq} WHERE user_id = '{$userId}'",
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Version/seq columns are removed by rolling back the schema migration
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        );
    }
}
