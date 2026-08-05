<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Data;

/**
 * Represents a single raw line in the .env file.
 * Preserves every byte for round-trip writing.
 */
class EnvLine
{
    public const TYPE_VARIABLE  = 'variable';
    public const TYPE_COMMENT   = 'comment';
    public const TYPE_BLANK     = 'blank';
    public const TYPE_SECTION   = 'section';  // e.g. "# ---- Database ----"

    public function __construct(
        public readonly string $type,
        public readonly string $raw,          // original raw line content (no trailing newline)
        public readonly ?string $key = null,
        public readonly ?string $value = null,
        public readonly ?string $quoteStyle = null,
        public readonly ?string $inlineComment = null,
    ) {}

    public function isVariable(): bool
    {
        return $this->type === self::TYPE_VARIABLE;
    }

    public function isComment(): bool
    {
        return $this->type === self::TYPE_COMMENT;
    }

    public function isBlank(): bool
    {
        return $this->type === self::TYPE_BLANK;
    }

    /**
     * Reconstruct the raw line string from key/value (used after edits).
     */
    public function toRaw(): string
    {
        if (! $this->isVariable()) {
            return $this->raw;
        }

        $value = $this->value ?? '';

        // Re-apply original quote style if present
        if ($this->quoteStyle !== null) {
            $value = $this->quoteStyle . $value . $this->quoteStyle;
        }

        $line = "{$this->key}={$value}";

        if ($this->inlineComment !== null) {
            $line .= ' ' . $this->inlineComment;
        }

        return $line;
    }

    public function withValue(string $newValue): self
    {
        return new self(
            type: $this->type,
            raw: $this->toRaw(),
            key: $this->key,
            value: $newValue,
            quoteStyle: $this->quoteStyle,
            inlineComment: $this->inlineComment,
        );
    }
}
