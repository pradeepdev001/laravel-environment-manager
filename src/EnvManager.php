<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager;

use Illuminate\Support\Collection;
use Pradeepdev\EnvironmentManager\Data\EnvLine;
use Pradeepdev\EnvironmentManager\Data\EnvVariable;
use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;
use Pradeepdev\EnvironmentManager\Services\AuditLogger;
use Pradeepdev\EnvironmentManager\Services\BackupManager;
use Pradeepdev\EnvironmentManager\Services\CacheManager;
use Pradeepdev\EnvironmentManager\Services\CategoryDetector;
use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\EnvWriter;
use Pradeepdev\EnvironmentManager\Services\NotificationDispatcher;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;
use Pradeepdev\EnvironmentManager\Services\ValidationEngine;
use Pradeepdev\EnvironmentManager\Services\VersionHistory;

class EnvManager
{
    private CategoryDetector $categoryDetector;

    public function __construct(
        private readonly EnvParser $parser,
        private readonly EnvWriter $writer,
        private readonly BackupManager $backupManager,
        private readonly VersionHistory $versionHistory,
        private readonly CacheManager $cacheManager,
        private readonly ValidationEngine $validator,
        private readonly SensitivityDetector $sensitivityDetector,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationDispatcher $notificationDispatcher,
    ) {
        $this->categoryDetector = new CategoryDetector(
            config('environment-manager.categories', []),
        );
    }

    /**
     * Get the path to the managed .env file.
     */
    public function getEnvPath(): string
    {
        return config('environment-manager.env_file_path', base_path('.env'));
    }

    /**
     * Parse and return all env variables as a Collection of EnvVariable objects.
     *
     * @return Collection<int, EnvVariable>
     */
    public function all(): Collection
    {
        $lines = $this->parser->parseFile($this->getEnvPath());

        return collect($lines)
            ->filter(fn (EnvLine $l) => $l->isVariable())
            ->values()
            ->map(fn (EnvLine $l) => $this->lineToVariable($l));
    }

    /**
     * Get a single variable by key.
     */
    public function get(string $key): ?EnvVariable
    {
        return $this->all()->first(fn (EnvVariable $v) => $v->key === $key);
    }

    /**
     * Set (add or update) a variable.
     *
     * @throws ValidationException
     */
    public function set(
        string $key,
        string $value,
        ?string $reason = null,
        string $source = 'ui',
    ): array {
        $this->validateKeyFormat($key);
        $this->validator->validateOrFail([$key => $value]);

        $envPath   = $this->getEnvPath();
        $lines     = $this->parser->parseFile($envPath);
        $keyMap    = $this->parser->toKeyMap($lines);
        $oldValue  = $keyMap[$key]?->value ?? null;
        $action    = array_key_exists($key, $keyMap) ? 'update' : 'create';
        $sensitive = $this->sensitivityDetector->isSensitive($key);

        // Backup before change
        $this->backupManager->create($envPath);

        // Apply change
        $lines = $this->writer->setVariable($lines, $key, $value);
        $this->writer->write($envPath, $lines);

        // Record history
        $this->versionHistory->record(
            action: $action,
            key: $key,
            oldValue: $oldValue,
            newValue: $value,
            sensitive: $sensitive,
            reason: $reason,
            source: $source,
        );

        // Notify
        $this->notificationDispatcher->dispatch($action, $key, $sensitive);

        // Clear caches
        $cacheResults = $this->cacheManager->run();

        return ['action' => $action, 'key' => $key, 'cache' => $cacheResults];
    }

    /**
     * Delete a variable by key.
     */
    public function delete(
        string $key,
        ?string $reason = null,
        string $source = 'ui',
    ): void {
        $envPath   = $this->getEnvPath();
        $lines     = $this->parser->parseFile($envPath);
        $keyMap    = $this->parser->toKeyMap($lines);
        $oldLine   = $keyMap[$key] ?? null;
        $sensitive = $this->sensitivityDetector->isSensitive($key);

        // Backup before change
        $this->backupManager->create($envPath);

        // Apply change
        $lines = $this->writer->deleteVariable($lines, $key);
        $this->writer->write($envPath, $lines);

        // Record history
        $this->versionHistory->record(
            action: 'delete',
            key: $key,
            oldValue: $oldLine?->value,
            newValue: null,
            sensitive: $sensitive,
            reason: $reason,
            source: $source,
        );

        $this->notificationDispatcher->dispatch('delete', $key, $sensitive);
        $this->cacheManager->run();
    }

    /**
     * Rename a key, preserving value and position.
     */
    public function rename(
        string $oldKey,
        string $newKey,
        ?string $reason = null,
        string $source = 'ui',
    ): void {
        $this->validateKeyFormat($newKey);

        $envPath   = $this->getEnvPath();
        $lines     = $this->parser->parseFile($envPath);
        $keyMap    = $this->parser->toKeyMap($lines);
        $value     = $keyMap[$oldKey]?->value ?? '';
        $sensitive = $this->sensitivityDetector->isSensitive($oldKey)
                  || $this->sensitivityDetector->isSensitive($newKey);

        $this->backupManager->create($envPath);

        $lines = $this->writer->renameVariable($lines, $oldKey, $newKey);
        $this->writer->write($envPath, $lines);

        $this->versionHistory->record(
            action: 'rename',
            key: $oldKey,
            oldValue: $value,
            newValue: $value,
            sensitive: $sensitive,
            reason: $reason ?? "Renamed to {$newKey}",
            source: $source,
        );

        $this->cacheManager->run();
    }

    /**
     * Bulk set multiple variables at once.
     *
     * @param  array<string, string>  $variables
     *
     * @throws ValidationException
     */
    public function bulkSet(
        array $variables,
        ?string $reason = null,
        string $source = 'ui',
    ): array {
        // Validate all before touching the file
        $this->validator->validateOrFail($variables);

        $envPath = $this->getEnvPath();
        $lines   = $this->parser->parseFile($envPath);
        $keyMap  = $this->parser->toKeyMap($lines);

        $this->backupManager->create($envPath);

        $changes = [];
        foreach ($variables as $key => $value) {
            $this->validateKeyFormat($key);
            $changes[$key] = [
                'old'       => $keyMap[$key]?->value ?? null,
                'new'       => $value,
                'sensitive' => $this->sensitivityDetector->isSensitive($key),
            ];
        }

        $lines = $this->writer->setMultiple($lines, $variables);
        $this->writer->write($envPath, $lines);

        $this->versionHistory->recordBulk($changes, $reason, $source);

        foreach ($variables as $key => $value) {
            $this->notificationDispatcher->dispatch(
                'bulk_update', $key, $this->sensitivityDetector->isSensitive($key),
            );
        }

        $cacheResults = $this->cacheManager->run();

        return ['count' => count($variables), 'cache' => $cacheResults];
    }

    /**
     * Get all variables as a simple key => value map.
     *
     * @return array<string, string>
     */
    public function toKeyValueMap(): array
    {
        $map = [];
        foreach ($this->all() as $variable) {
            $map[$variable->key] = $variable->rawValue;
        }

        return $map;
    }

    /**
     * Convert an EnvLine to an EnvVariable with category, type, sensitivity metadata.
     */
    private function lineToVariable(EnvLine $line): EnvVariable
    {
        $key       = $line->key   ?? '';
        $value     = $line->value ?? '';
        $sensitive = $this->sensitivityDetector->isSensitive($key);
        $category  = $this->categoryDetector->detect($key);

        return new EnvVariable(
            key: $key,
            rawValue: $value,
            type: $this->detectType($value),
            category: $category,
            description: '',
            sensitive: $sensitive,
            lineNumber: 0,
            quoteStyle: $line->quoteStyle,
        );
    }

    private function detectType(string $value): string
    {
        if (in_array(strtolower($value), ['true', 'false', '(true)', '(false)'], true)) {
            return 'boolean';
        }
        if ($value === '' || in_array(strtolower($value), ['null', '(null)'], true)) {
            return 'null';
        }
        if (is_numeric($value) && ! str_contains($value, '.')) {
            return 'integer';
        }

        return 'string';
    }

    private function validateKeyFormat(string $key): void
    {
        if (! preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
            throw new \InvalidArgumentException(
                "Invalid env key format: [{$key}]. Keys must match [A-Z_][A-Z0-9_]*.",
            );
        }
    }
}
