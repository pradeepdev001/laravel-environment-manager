<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;

class GetCommand extends Command
{
    protected $signature = 'env-manager:get
        {key : The variable key to retrieve}
        {--reveal : Show the actual value (only allowed in local env)}';

    protected $description = 'Get the value of a single environment variable.';

    public function handle(EnvManager $manager): int
    {
        $key      = strtoupper($this->argument('key'));
        $variable = $manager->get($key);

        if ($variable === null) {
            $this->error("Variable [{$key}] not found in .env file.");
            return self::FAILURE;
        }

        $reveal = $this->option('reveal') && config('app.env') === 'local';

        $this->table(
            ['Key', 'Value', 'Type', 'Category', 'Sensitive'],
            [[
                $variable->key,
                $variable->getDisplayValue($reveal),
                $variable->type,
                $variable->category,
                $variable->sensitive ? 'Yes' : 'No',
            ]]
        );

        if ($this->option('reveal') && ! $reveal) {
            $this->warn('--reveal is only permitted in APP_ENV=local environments.');
        }

        return self::SUCCESS;
    }
}
