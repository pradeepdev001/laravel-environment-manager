<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Exceptions;

use RuntimeException;

class BackupStorageException extends RuntimeException
{
    public static function notWritable(string $path): self
    {
        return new self("The backup storage path [{$path}] is not writable.");
    }
}
