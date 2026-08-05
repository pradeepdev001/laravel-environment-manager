<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Pradeepdev\EnvironmentManager\Data\EnvLine;
use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;

class ImportProcessor
{
    public function __construct(
        private readonly EnvParser $parser,
        private readonly ValidationEngine $validator,
    ) {}

    /**
     * Parse a .env format string into a key-value map.
     *
     * @return array<string, string>
     */
    public function parseEnvString(string $contents): array
    {
        $lines = $this->parser->parseContents($contents);
        $result = [];

        foreach ($lines as $line) {
            if ($line->isVariable() && $line->key !== null) {
                $result[$line->key] = $line->value ?? '';
            }
        }

        return $result;
    }

    /**
     * Parse a JSON file/string into a key-value map.
     *
     * @return array<string, string>
     * @throws \InvalidArgumentException
     */
    public function parseJsonString(string $contents): array
    {
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid JSON: must be a JSON object with string key-value pairs.');
        }

        $result = [];
        foreach ($decoded as $key => $value) {
            if (! is_string($key)) {
                throw new \InvalidArgumentException("JSON key must be a string, got: " . gettype($key));
            }
            $result[$key] = (string) $value;
        }

        return $result;
    }

    /**
     * Validate an import payload.
     * Throws ValidationException if any variable fails validation.
     *
     * @param  array<string, string>  $variables
     * @throws ValidationException
     */
    public function validate(array $variables): void
    {
        $errors = $this->validator->validateMany($variables);

        if (! empty($errors)) {
            throw new ValidationException($errors, 'Import validation failed. No changes were applied.');
        }
    }

    /**
     * Process an .env format import: parse, validate, return variables.
     * Does NOT write to disk — caller is responsible for applying.
     *
     * @return array<string, string>
     * @throws ValidationException
     */
    public function processEnv(string $contents): array
    {
        $variables = $this->parseEnvString($contents);
        $this->validate($variables);

        return $variables;
    }

    /**
     * Process a JSON import: parse, validate, return variables.
     *
     * @return array<string, string>
     * @throws ValidationException|\InvalidArgumentException
     */
    public function processJson(string $contents): array
    {
        $variables = $this->parseJsonString($contents);
        $this->validate($variables);

        return $variables;
    }
}
