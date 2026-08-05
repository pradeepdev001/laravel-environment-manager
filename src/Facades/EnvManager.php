<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static \Pradeepdev\EnvironmentManager\Data\EnvVariable|null get(string $key)
 * @method static array set(string $key, string $value, ?string $reason = null, string $source = 'ui')
 * @method static void delete(string $key, ?string $reason = null, string $source = 'ui')
 * @method static void rename(string $oldKey, string $newKey, ?string $reason = null, string $source = 'ui')
 * @method static array bulkSet(array $variables, ?string $reason = null, string $source = 'ui')
 * @method static array<string, string> toKeyValueMap()
 * @method static string getEnvPath()
 *
 * @see \Pradeepdev\EnvironmentManager\EnvManager
 */
class EnvManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'env-manager';
    }
}
