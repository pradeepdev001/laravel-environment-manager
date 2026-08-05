<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

class SensitivityDetector
{
    /** @var string[] */
    private array $patterns;

    private static array $defaultPatterns = [
        'APP_KEY',
        '*_PASSWORD',
        '*_SECRET',
        '*_SECRET_KEY',
        '*_API_KEY',
        '*_TOKEN',
        '*_PRIVATE_KEY',
        '*_ACCESS_KEY',
        'JWT_SECRET',
        'STRIPE_*',
        'AWS_SECRET*',
    ];

    /**
     * @param  string[]  $extraPatterns  Additional patterns from config merged with defaults.
     */
    public function __construct(array $extraPatterns = [])
    {
        $this->patterns = array_unique(
            array_merge(self::$defaultPatterns, $extraPatterns)
        );
    }

    public function isSensitive(string $key): bool
    {
        $upperKey = strtoupper($key);

        foreach ($this->patterns as $pattern) {
            if ($this->matchesPattern($upperKey, strtoupper($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a key against a glob-style pattern (only * wildcard supported).
     */
    private function matchesPattern(string $key, string $pattern): bool
    {
        // Exact match
        if ($key === $pattern) {
            return true;
        }

        // Convert glob pattern to regex
        $regex = '/^' . str_replace(
            ['\\*', '\\?'],
            ['.*', '.'],
            preg_quote($pattern, '/')
        ) . '$/';

        return (bool) preg_match($regex, $key);
    }

    /** @return string[] */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    public function mask(string $key, string $value, bool $reveal = false): string
    {
        if ($reveal) {
            return $value;
        }

        return $this->isSensitive($key) ? '••••••••' : $value;
    }
}
