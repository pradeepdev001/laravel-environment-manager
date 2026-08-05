<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\Services\DiffEngine;
use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;

class CompareCommand extends Command
{
    protected $signature = 'env-manager:compare
        {env1 : Path or named environment key (from config) for the first file}
        {env2 : Path or named environment key (from config) for the second file}
        {--show-unchanged : Also show unchanged variables}';

    protected $description = 'Compare two .env files and show differences.';

    public function handle(
        EnvParser $parser,
        DiffEngine $diffEngine,
        SensitivityDetector $detector,
    ): int {
        $path1 = $this->resolvePath($this->argument('env1'));
        $path2 = $this->resolvePath($this->argument('env2'));

        if (! file_exists($path1)) {
            $this->error("File not found: [{$path1}]");
            return self::FAILURE;
        }

        if (! file_exists($path2)) {
            $this->error("File not found: [{$path2}]");
            return self::FAILURE;
        }

        $map1 = $parser->toKeyMap($parser->parseFile($path1));
        $map2 = $parser->toKeyMap($parser->parseFile($path2));

        $values1 = array_map(fn ($l) => $l->value ?? '', $map1);
        $values2 = array_map(fn ($l) => $l->value ?? '', $map2);

        $diff = $diffEngine->diff($values1, $values2);
        $diff = $diffEngine->maskSensitive($diff, $detector);

        $showUnchanged = $this->option('show-unchanged');

        $rows = [];
        foreach ($diff as $key => $entry) {
            if (! $showUnchanged && $entry['status'] === DiffEngine::STATUS_UNCHANGED) {
                continue;
            }

            $status = match ($entry['status']) {
                DiffEngine::STATUS_ADDED    => '<fg=green>+ ADDED</>',
                DiffEngine::STATUS_REMOVED  => '<fg=red>- REMOVED</>',
                DiffEngine::STATUS_MODIFIED => '<fg=yellow>~ MODIFIED</>',
                default                     => '  unchanged',
            };

            $rows[] = [
                $key,
                $status,
                $entry['old'] ?? '(none)',
                $entry['new'] ?? '(none)',
            ];
        }

        if (empty($rows)) {
            $this->info('The two files are identical.');
            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Status', basename($path1), basename($path2)],
            $rows
        );

        $added    = count(array_filter($diff, fn ($e) => $e['status'] === DiffEngine::STATUS_ADDED));
        $removed  = count(array_filter($diff, fn ($e) => $e['status'] === DiffEngine::STATUS_REMOVED));
        $modified = count(array_filter($diff, fn ($e) => $e['status'] === DiffEngine::STATUS_MODIFIED));

        $this->line("\n<fg=green>+{$added} added</> <fg=red>-{$removed} removed</> <fg=yellow>~{$modified} modified</>");

        return self::SUCCESS;
    }

    private function resolvePath(string $input): string
    {
        // Check if it's a named env from config
        $environments = config('environment-manager.environments', []);
        if (isset($environments[$input])) {
            return $environments[$input];
        }

        return $input;
    }
}
