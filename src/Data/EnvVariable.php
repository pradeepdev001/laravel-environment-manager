<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Data;

class EnvVariable
{
    public function __construct(
        public readonly string $key,
        public readonly string $rawValue,
        public readonly string $type,        // string | boolean | integer | null
        public readonly string $category,
        public readonly string $description,
        public readonly bool $sensitive,
        public readonly int $lineNumber,
        public readonly ?string $quoteStyle = null,   // null | '"' | "'"
        public readonly bool $isComment = false,
        public readonly bool $isBlankLine = false,
        public readonly string $commentText = '',
    ) {}

    public function getValue(): string
    {
        return $this->rawValue;
    }

    public function getDisplayValue(bool $reveal = false): string
    {
        if ($this->sensitive && ! $reveal) {
            return '••••••••';
        }

        return $this->rawValue;
    }

    public function withValue(string $newValue): self
    {
        return new self(
            key: $this->key,
            rawValue: $newValue,
            type: $this->detectType($newValue),
            category: $this->category,
            description: $this->description,
            sensitive: $this->sensitive,
            lineNumber: $this->lineNumber,
            quoteStyle: $this->quoteStyle,
            isComment: $this->isComment,
            isBlankLine: $this->isBlankLine,
            commentText: $this->commentText,
        );
    }

    public function withKey(string $newKey): self
    {
        return new self(
            key: $newKey,
            rawValue: $this->rawValue,
            type: $this->type,
            category: $this->category,
            description: $this->description,
            sensitive: $this->sensitive,
            lineNumber: $this->lineNumber,
            quoteStyle: $this->quoteStyle,
            isComment: $this->isComment,
            isBlankLine: $this->isBlankLine,
            commentText: $this->commentText,
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

    public function toArray(): array
    {
        return [
            'key'         => $this->key,
            'value'       => $this->rawValue,
            'type'        => $this->type,
            'category'    => $this->category,
            'description' => $this->description,
            'sensitive'   => $this->sensitive,
            'line_number' => $this->lineNumber,
        ];
    }
}
