<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Symfony\Component\Yaml\Yaml;

class ExportFormatter
{
    public function __construct(
        private readonly SensitivityDetector $detector,
    ) {}

    /**
     * Export to .env format string.
     *
     * @param  array<string, string>  $variables
     */
    public function toEnv(array $variables, bool $reveal = false): string
    {
        $lines = [];
        foreach ($variables as $key => $value) {
            $displayValue = $this->detector->mask($key, $value, $reveal);
            // Quote values that contain spaces or special chars
            if (preg_match('/[\s#"\'=]/', $displayValue)) {
                $displayValue = '"' . addcslashes($displayValue, '"\\') . '"';
            }
            $lines[] = "{$key}={$displayValue}";
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Export to JSON format string.
     *
     * @param  array<string, string>  $variables
     */
    public function toJson(array $variables, bool $reveal = false): string
    {
        $masked = [];
        foreach ($variables as $key => $value) {
            $masked[$key] = $this->detector->mask($key, $value, $reveal);
        }

        return json_encode($masked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export to YAML format string.
     *
     * @param  array<string, string>  $variables
     */
    public function toYaml(array $variables, bool $reveal = false): string
    {
        $masked = [];
        foreach ($variables as $key => $value) {
            $masked[$key] = $this->detector->mask($key, $value, $reveal);
        }

        return Yaml::dump($masked, 2, 2);
    }
}
