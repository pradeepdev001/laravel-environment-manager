<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Environment variable validation failed.'
    ) {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
