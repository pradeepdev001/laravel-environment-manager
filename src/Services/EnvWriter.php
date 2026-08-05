<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Pradeepdev\EnvironmentManager\Data\EnvLine;
use Pradeepdev\EnvironmentManager\Exceptions\FileLockException;

class EnvWriter
{
    private const LOCK_RETRIES = 3;
    private const LOCK_WAIT_MS = 100;

    /**
     * Write an ordered list of EnvLine objects to a .env file.
     * Uses atomic write (temp file + rename) and exclusive file locking.
     *
     * @param  EnvLine[]  $lines
     */
    public function write(string $path, array $lines): void
    {
        $contents = $this->linesToString($lines);
        $this->atomicWrite($path, $contents);
    }

    /**
     * Render EnvLine[] back to a .env file string.
     *
     * @param  EnvLine[]  $lines
     */
    public function linesToString(array $lines): string
    {
        $parts = [];
        foreach ($lines as $line) {
            $parts[] = $line->toRaw();
        }

        return implode("\n", $parts);
    }

    /**
     * Atomically replace $path with $contents.
     * Writes to a temp file first, then renames to guarantee atomicity.
     */
    public function atomicWrite(string $path, string $contents): void
    {
        $dir = dirname($path);
        $tmp = tempnam($dir, '.env_tmp_');

        if ($tmp === false) {
            throw new \RuntimeException("Could not create temp file in [{$dir}].");
        }

        try {
            $handle = fopen($tmp, 'w');
            if ($handle === false) {
                throw new \RuntimeException("Could not open temp file [{$tmp}] for writing.");
            }

            $locked = false;
            for ($attempt = 0; $attempt < self::LOCK_RETRIES; $attempt++) {
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    $locked = true;
                    break;
                }
                usleep(self::LOCK_WAIT_MS * 1_000);
            }

            if (! $locked) {
                fclose($handle);
                @unlink($tmp);
                throw FileLockException::timeout($path);
            }

            fwrite($handle, $contents);
            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);

            // Atomic rename — on POSIX systems this is guaranteed atomic
            if (! rename($tmp, $path)) {
                throw new \RuntimeException("Could not rename temp file [{$tmp}] to [{$path}].");
            }
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }
    }

    /**
     * Apply a single key-value update to the parsed lines.
     * Returns the modified lines array.
     *
     * @param  EnvLine[]  $lines
     * @return EnvLine[]
     */
    public function setVariable(array $lines, string $key, string $value): array
    {
        $found = false;

        foreach ($lines as $i => $line) {
            if ($line->isVariable() && $line->key === $key) {
                $lines[$i] = $line->withValue($value);
                $found = true;
                break;
            }
        }

        if (! $found) {
            $lines[] = new EnvLine(
                type: EnvLine::TYPE_VARIABLE,
                raw: "{$key}={$value}",
                key: $key,
                value: $value,
            );
        }

        return $lines;
    }

    /**
     * Remove a key from the parsed lines.
     *
     * @param  EnvLine[]  $lines
     * @return EnvLine[]
     */
    public function deleteVariable(array $lines, string $key): array
    {
        return array_values(
            array_filter($lines, fn (EnvLine $l) => ! ($l->isVariable() && $l->key === $key))
        );
    }

    /**
     * Rename a key (preserves value and position).
     *
     * @param  EnvLine[]  $lines
     * @return EnvLine[]
     */
    public function renameVariable(array $lines, string $oldKey, string $newKey): array
    {
        foreach ($lines as $i => $line) {
            if ($line->isVariable() && $line->key === $oldKey) {
                $newRaw = "{$newKey}=" . ($line->quoteStyle
                    ? $line->quoteStyle . $line->value . $line->quoteStyle
                    : $line->value);

                $lines[$i] = new EnvLine(
                    type: EnvLine::TYPE_VARIABLE,
                    raw: $newRaw,
                    key: $newKey,
                    value: $line->value,
                    quoteStyle: $line->quoteStyle,
                    inlineComment: $line->inlineComment,
                );
                break;
            }
        }

        return $lines;
    }

    /**
     * Apply multiple key-value pairs at once.
     *
     * @param  EnvLine[]  $lines
     * @param  array<string, string>  $variables
     * @return EnvLine[]
     */
    public function setMultiple(array $lines, array $variables): array
    {
        foreach ($variables as $key => $value) {
            $lines = $this->setVariable($lines, $key, $value);
        }

        return $lines;
    }
}
