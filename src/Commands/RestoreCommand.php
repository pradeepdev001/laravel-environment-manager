<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Services\BackupManager;
use Pradeepdev\EnvironmentManager\Services\VersionHistory;

class RestoreCommand extends Command
{
    protected $signature = 'env-manager:restore
        {backup_name? : The backup filename to restore (omit to list available backups)}
        {--force      : Skip confirmation prompt}';

    protected $description = 'Restore the .env file from a backup.';

    public function handle(
        EnvManager $manager,
        BackupManager $backupManager,
        VersionHistory $history,
    ): int {
        $backups = $backupManager->list();

        if (empty($backups)) {
            $this->warn('No backups available.');
            return self::SUCCESS;
        }

        $backupName = $this->argument('backup_name');

        if ($backupName === null) {
            // Interactive selection
            $choices = array_column($backups, 'filename');
            $backupName = $this->choice('Select a backup to restore:', $choices);
        }

        $backupPath = rtrim(config('environment-manager.backup_path'), '/') . '/' . $backupName;

        if (! file_exists($backupPath)) {
            $this->error("Backup [{$backupName}] not found.");
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            "This will overwrite your current .env with [{$backupName}]. Continue?"
        )) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        $envPath = $manager->getEnvPath();

        // Backup current before restoring
        $backupManager->create($envPath);

        $backupManager->restore($backupPath, $envPath);

        $history->record(
            action: 'restore',
            key: '*',
            oldValue: null,
            newValue: null,
            reason: "Restored from backup: {$backupName}",
            source: 'cli',
        );

        $this->info("Environment restored from [{$backupName}].");

        return self::SUCCESS;
    }
}
