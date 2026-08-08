<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Pradeepdev\EnvironmentManager\Data\EnvLine;
use Pradeepdev\EnvironmentManager\Exceptions\EnvFileNotFoundException;

class EnvParser
{
    /**
     * Parse a .env file into an ordered list of EnvLine objects.
     * Preserves comments, blank lines, and formatting for round-trip writing.
     *
     * @return EnvLine[]
     */
    public function parseFile(string $path): array
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw EnvFileNotFoundException::forPath($path);
        }

        $size = filesize($path);
        if ($size > 1_048_576) {
            $this->warn("EnvironmentManager: .env file exceeds 1MB ({$size} bytes) at [{$path}].");
        }

        $contents = file_get_contents($path);

        return $this->parseContents($contents === false ? '' : $contents);
    }

    /**
     * Parse raw .env string content into an ordered list of EnvLine objects.
     *
     * @return EnvLine[]
     */
    public function parseContents(string $contents): array
    {
        $lines    = [];
        $rawLines = explode("\n", $contents);
        // If file ends with \n, last element will be empty — we keep it to preserve the trailing newline
        $lineNumber = 0;

        foreach ($rawLines as $raw) {
            $lineNumber++;
            $trimmed = trim($raw);

            // Blank line
            if ($trimmed === '') {
                $lines[] = new EnvLine(
                    type: EnvLine::TYPE_BLANK,
                    raw: $raw,
                );

                continue;
            }

            // Comment line
            if (str_starts_with($trimmed, '#')) {
                $lines[] = new EnvLine(
                    type: EnvLine::TYPE_COMMENT,
                    raw: $raw,
                );

                continue;
            }

            // Variable line
            if (str_contains($trimmed, '=')) {
                [$key, $parsedValue, $quoteStyle, $inlineComment] = $this->parseLine($trimmed, $raw);

                if ($key !== null) {
                    $lines[] = new EnvLine(
                        type: EnvLine::TYPE_VARIABLE,
                        raw: $raw,
                        key: $key,
                        value: $parsedValue,
                        quoteStyle: $quoteStyle,
                        inlineComment: $inlineComment,
                    );

                    continue;
                }
            }

            // Malformed line — preserve as-is and log a warning
            $this->warn("EnvironmentManager: Malformed .env line {$lineNumber} skipped: [{$raw}]");
            $lines[] = new EnvLine(
                type: EnvLine::TYPE_COMMENT,  // treat as comment to preserve
                raw: $raw,
            );
        }

        return $lines;
    }

    /**
     * Extract key, parsed value, quote style, and inline comment from a variable line.
     *
     * @return array{0: ?string, 1: string, 2: ?string, 3: ?string}
     */
    private function parseLine(string $trimmed, string $raw): array
    {
        $eqPos = strpos($trimmed, '=');
        if ($eqPos === false) {
            return [null, '', null, null];
        }

        $key = trim(substr($trimmed, 0, $eqPos));

        // Validate key format
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $key)) {
            return [null, '', null, null];
        }

        $rest          = substr($trimmed, $eqPos + 1);
        $quoteStyle    = null;
        $inlineComment = null;
        $value         = '';

        if ($rest === '') {
            return [$key, '', null, null];
        }

        // Quoted value
        if ($rest[0] === '"' || $rest[0] === "'") {
            $quote    = $rest[0];
            $closePos = $this->findClosingQuote($rest, $quote);

            if ($closePos !== false) {
                $value      = substr($rest, 1, $closePos - 1);
                $quoteStyle = $quote;
                $after      = trim(substr($rest, $closePos + 1));
                if (str_starts_with($after, '#')) {
                    $inlineComment = $after;
                }
            } else {
                // Unclosed quote — take the whole thing as value
                $value = $rest;
            }

            return [$key, $value, $quoteStyle, $inlineComment];
        }

        // Unquoted value — strip inline comment
        $commentPos = strpos($rest, ' #');
        if ($commentPos !== false) {
            $value         = rtrim(substr($rest, 0, $commentPos));
            $inlineComment = trim(substr($rest, $commentPos + 1));
        } else {
            $value = $rest;
        }

        return [$key, $value, null, $inlineComment ?: null];
    }

    /**
     * Find the position of the closing quote character, respecting backslash escaping.
     */
    private function findClosingQuote(string $str, string $quote): int|false
    {
        $len = strlen($str);
        for ($i = 1; $i < $len; $i++) {
            if ($str[$i] === '\\') {
                $i++; // skip escaped character

                continue;
            }
            if ($str[$i] === $quote) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Extract only the variable EnvLines as a key => EnvLine map.
     *
     * @param  EnvLine[]  $lines
     * @return array<string, EnvLine>
     */
    public function toKeyMap(array $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            if ($line->isVariable() && $line->key !== null) {
                $map[$line->key] = $line;
            }
        }

        return $map;
    }

    /**
     * Get all variable keys from parsed lines.
     *
     * @param  EnvLine[]  $lines
     * @return string[]
     */
    public function getKeys(array $lines): array
    {
        return array_keys($this->toKeyMap($lines));
    }

    /**
     * Log a warning — uses Laravel's Log facade when available, falls back to error_log.
     */
    private function warn(string $message): void
    {
        if (class_exists(Log::class) && Facade::getFacadeApplication() !== null) {
            Log::warning($message);
        } else {
            error_log($message);
        }
    }
}
