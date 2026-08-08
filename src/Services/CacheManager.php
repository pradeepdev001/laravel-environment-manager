<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CacheManager
{
    /** Whitelisted Artisan commands that may be executed */
    private const ALLOWED_COMMANDS = [
        'config:clear',
        'config:cache',
        'route:clear',
        'route:cache',
        'view:clear',
        'view:cache',
        'event:clear',
        'event:cache',
        'optimize',
        'optimize:clear',
    ];

    public function __construct(
        private readonly array $commands,
        private readonly bool $runAfterSave,
        private readonly bool $runInLocal,
        private readonly string $appEnv,
    ) {}

    /**
     * Run configured cache commands.
     * Returns a result map: ['command' => ['success' => bool, 'output' => string]]
     *
     * @return array<string, array{success: bool, output: string}>
     */
    public function run(): array
    {
        if (! $this->runAfterSave) {
            return [];
        }

        if ($this->appEnv === 'local' && ! $this->runInLocal) {
            return [];
        }

        $results = [];

        foreach ($this->commands as $command) {
            if (! in_array($command, self::ALLOWED_COMMANDS, true)) {
                Log::warning("EnvironmentManager: Skipping non-whitelisted cache command [{$command}].");
                $results[$command] = ['success' => false, 'output' => 'Command not whitelisted.'];

                continue;
            }

            try {
                $exitCode = Artisan::call($command);
                $output   = Artisan::output();
                $success  = $exitCode === 0;

                if (! $success) {
                    Log::warning("EnvironmentManager: Cache command [{$command}] exited with code {$exitCode}.");
                }

                $results[$command] = ['success' => $success, 'output' => trim($output)];
            } catch (\Throwable $e) {
                Log::error("EnvironmentManager: Cache command [{$command}] failed: {$e->getMessage()}");
                $results[$command] = ['success' => false, 'output' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function getAllowedCommands(): array
    {
        return self::ALLOWED_COMMANDS;
    }
}
