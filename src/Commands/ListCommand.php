<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;

class ListCommand extends Command
{
    protected $signature = 'env-manager:list
        {--category= : Filter by category}
        {--search=   : Search by key name}
        {--format=table : Output format (table|json)}';

    protected $description = 'List all environment variables.';

    public function handle(EnvManager $manager): int
    {
        $variables = $manager->all();

        if ($category = $this->option('category')) {
            $variables = $variables->filter(fn ($v) => strtolower($v->category) === strtolower($category));
        }

        if ($search = $this->option('search')) {
            $variables = $variables->filter(fn ($v) => str_contains(strtolower($v->key), strtolower($search)));
        }

        if ($variables->isEmpty()) {
            $this->warn('No variables found matching the given criteria.');

            return self::SUCCESS;
        }

        if ($this->option('format') === 'json') {
            $this->line(json_encode(
                array_values($variables->map(fn ($v) => $v->toArray())->all()),
                JSON_PRETTY_PRINT,
            ));

            return self::SUCCESS;
        }

        $rows = $variables->map(fn ($v) => [
            $v->key,
            $v->getDisplayValue(),
            $v->type,
            $v->category,
        ])->toArray();

        $this->table(['Key', 'Value', 'Type', 'Category'], $rows);

        return self::SUCCESS;
    }
}
