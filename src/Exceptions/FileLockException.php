<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Exceptions;

use RuntimeException;

class FileLockException extends RuntimeException
{
    public static function timeout(string $path): self
    {
        return new self("Could not acquire file lock on [{$path}] after maximum retries.");
    }
}
