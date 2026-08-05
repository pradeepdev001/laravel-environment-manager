<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;

class ValidationEngine
{
    private array $rules;

    public function __construct(array $configRules = [])
    {
        $this->rules = array_merge($this->defaultRules(), $configRules);
    }

    /**
     * Validate a single key => value pair.
     * Returns an array of error messages (empty = valid).
     *
     * @return string[]
     */
    public function validateOne(string $key, string $value): array
    {
        $errors = [];
        $keyRules = $this->rules[$key] ?? [];

        foreach ($keyRules as $rule) {
            $error = $this->applyRule($key, $value, $rule);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Validate multiple key-value pairs.
     * Returns array<key, string[]> of errors. Empty = all valid.
     *
     * @param  array<string, string>  $variables
     * @return array<string, string[]>
     */
    public function validateMany(array $variables): array
    {
        $allErrors = [];

        foreach ($variables as $key => $value) {
            $errors = $this->validateOne($key, $value);
            if (! empty($errors)) {
                $allErrors[$key] = $errors;
            }
        }

        return $allErrors;
    }

    /**
     * Validate and throw if there are any errors.
     *
     * @param  array<string, string>  $variables
     * @throws ValidationException
     */
    public function validateOrFail(array $variables): void
    {
        $errors = $this->validateMany($variables);

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function applyRule(string $key, string $value, string $rule): ?string
    {
        // nullable — empty value is allowed, skip further checks
        if ($rule === 'nullable' && $value === '') {
            return null;
        }

        // required
        if ($rule === 'required' && $value === '') {
            return "{$key} is required and cannot be empty.";
        }

        // Skip remaining rules for empty non-required values
        if ($value === '') {
            return null;
        }

        if ($rule === 'url') {
            if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                return "{$key} must be a valid URL.";
            }
            return null;
        }

        if ($rule === 'email') {
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                return "{$key} must be a valid email address.";
            }
            return null;
        }

        if ($rule === 'integer') {
            if (! ctype_digit(ltrim($value, '-'))) {
                return "{$key} must be an integer.";
            }
            return null;
        }

        if ($rule === 'boolean') {
            $valid = ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off', '(true)', '(false)'];
            if (! in_array(strtolower($value), $valid, true)) {
                return "{$key} must be a boolean value (true/false/1/0).";
            }
            return null;
        }

        if (str_starts_with($rule, 'enum:')) {
            $allowed = explode(',', substr($rule, 5));
            if (! in_array($value, $allowed, true)) {
                return "{$key} must be one of: " . implode(', ', $allowed) . '.';
            }
            return null;
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (is_numeric($value) && (int) $value < $min) {
                return "{$key} must be at least {$min}.";
            }
            return null;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (is_numeric($value) && (int) $value > $max) {
                return "{$key} must not exceed {$max}.";
            }
            return null;
        }

        if (str_starts_with($rule, 'regex:')) {
            $pattern = substr($rule, 6);
            if (! preg_match($pattern, $value)) {
                return "{$key} format is invalid.";
            }
            return null;
        }

        return null;
    }

    private function defaultRules(): array
    {
        return [
            'APP_URL'          => ['url'],
            'APP_ENV'          => ['enum:local,development,staging,testing,production'],
            'APP_DEBUG'        => ['boolean'],
            'MAIL_PORT'        => ['integer', 'min:1', 'max:65535'],
            'DB_CONNECTION'    => ['enum:mysql,pgsql,sqlite,sqlsrv,mariadb'],
            'CACHE_DRIVER'     => ['enum:array,file,database,redis,memcached,dynamodb,octane'],
            'QUEUE_CONNECTION' => ['enum:sync,database,beanstalkd,sqs,redis,null'],
            'SESSION_DRIVER'   => ['enum:file,cookie,database,apc,memcached,redis,dynamodb,array'],
            'MAIL_MAILER'      => ['enum:smtp,sendmail,mailgun,ses,sparkpost,log,array,failover'],
            'DB_PORT'          => ['integer', 'min:1', 'max:65535'],
            'REDIS_PORT'       => ['integer', 'min:1', 'max:65535'],
        ];
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
