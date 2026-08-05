<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Services\BackupManager;

class BackupCommand extends Command
{
    protected $signature = 'env-manager:backup';

    protected $description = 'Create a manual backup of the current .env file.';

    public function handle(EnvManager $manager, BackupManager $backupManager): int
    {
        $envPath    = $manager->getEnvPath();
        $backupPath = $backupManager->create($envPath);

        $this->info('Backup created successfully.');
        $this->line("  Path: {$backupPath}");

        return self::SUCCESS;
    }
}
