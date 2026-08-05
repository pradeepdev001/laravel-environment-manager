<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;

class SetCommand extends Command
{
    protected $signature = 'env-manager:set
        {key   : The variable key (must be UPPER_SNAKE_CASE)}
        {value : The value to set}
        {--reason= : Optional reason for this change (stored in history)}';

    protected $description = 'Add or update an environment variable.';

    public function handle(EnvManager $manager): int
    {
        $key    = strtoupper($this->argument('key'));
        $value  = $this->argument('value');
        $reason = $this->option('reason');

        try {
            $result = $manager->set(
                key: $key,
                value: $value,
                reason: $reason,
                source: 'cli',
            );

            $action = $result['action'] === 'create' ? 'Created' : 'Updated';
            $this->info("{$action}: [{$key}] saved successfully.");

            if (! empty($result['cache'])) {
                foreach ($result['cache'] as $cmd => $res) {
                    $status = $res['success'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
                    $this->line("  {$status} {$cmd}");
                }
            }

            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $errorKey => $messages) {
                foreach ((array) $messages as $msg) {
                    $this->error("[{$errorKey}] {$msg}");
                }
            }
            return self::FAILURE;
        }
    }
}
