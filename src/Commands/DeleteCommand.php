<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;

class DeleteCommand extends Command
{
    protected $signature = 'env-manager:delete
        {key : The variable key to delete}
        {--reason= : Optional reason for the deletion}
        {--force   : Skip confirmation prompt}';

    protected $description = 'Delete an environment variable.';

    public function handle(EnvManager $manager): int
    {
        $key = strtoupper($this->argument('key'));

        if ($manager->get($key) === null) {
            $this->error("Variable [{$key}] not found in .env file.");
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to delete [{$key}]?")) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        $manager->delete($key, $this->option('reason'), 'cli');

        $this->info("Variable [{$key}] deleted successfully.");

        return self::SUCCESS;
    }
}
