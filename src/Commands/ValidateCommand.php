<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Services\ValidationEngine;

class ValidateCommand extends Command
{
    protected $signature = 'env-manager:validate';

    protected $description = 'Validate the current .env file against configured validation rules.';

    public function handle(EnvManager $manager, ValidationEngine $validator): int
    {
        $variables = $manager->toKeyValueMap();
        $rules     = $validator->getRules();
        $rows      = [];
        $hasErrors = false;

        foreach ($rules as $key => $keyRules) {
            $value  = $variables[$key] ?? '';
            $errors = $validator->validateOne($key, $value);

            if (empty($errors)) {
                $rows[] = [$key, $value ?: '(empty)', '<fg=green>✓ Pass</>', ''];
            } else {
                $hasErrors = true;
                $rows[]    = [$key, $value ?: '(empty)', '<fg=red>✗ Fail</>', implode(' | ', $errors)];
            }
        }

        if (empty($rows)) {
            $this->info('No validation rules configured.');

            return self::SUCCESS;
        }

        $this->table(['Key', 'Current Value', 'Status', 'Errors'], $rows);

        if ($hasErrors) {
            $this->error('Validation failed. See errors above.');

            return self::FAILURE;
        }

        $this->info('All variables pass validation.');

        return self::SUCCESS;
    }
}
