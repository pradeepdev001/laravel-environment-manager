<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Exceptions;

use RuntimeException;

class EnvFileNotFoundException extends RuntimeException
{
    public static function forPath(string $path): self
    {
        return new self("The .env file was not found or is not readable at path: [{$path}]");
    }
}
