<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\ExportFormatter;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;

beforeEach(function () {
    $this->formatter = new ExportFormatter(new SensitivityDetector);

    $this->vars = [
        'APP_NAME'    => 'Laravel',
        'APP_ENV'     => 'local',
        'APP_KEY'     => 'base64:abc123==',
        'DB_PASSWORD' => 'secret',
    ];
});

// .env export
it('exports to .env format', function () {
    $output = $this->formatter->toEnv($this->vars);

    expect($output)->toContain('APP_NAME=Laravel')
        ->and($output)->toContain('APP_ENV=local');
});

it('masks sensitive values in .env export by default', function () {
    $output = $this->formatter->toEnv($this->vars);

    expect($output)->not->toContain('base64:abc123==')
        ->and($output)->not->toContain('secret')
        ->and($output)->toContain('••••••••');
});

it('reveals sensitive values in .env export when reveal=true', function () {
    $output = $this->formatter->toEnv($this->vars, reveal: true);

    expect($output)->toContain('base64:abc123==')
        ->and($output)->toContain('secret');
});

// JSON export
it('exports to JSON format', function () {
    $output = $this->formatter->toJson($this->vars);
    $data   = json_decode($output, true);

    expect($data)->toHaveKey('APP_NAME')
        ->and($data['APP_NAME'])->toBe('Laravel');
});

it('masks sensitive values in JSON export by default', function () {
    $output = $this->formatter->toJson($this->vars);
    $data   = json_decode($output, true);

    expect($data['APP_KEY'])->toBe('••••••••')
        ->and($data['DB_PASSWORD'])->toBe('••••••••');
});

it('reveals sensitive values in JSON export when reveal=true', function () {
    $output = $this->formatter->toJson($this->vars, reveal: true);
    $data   = json_decode($output, true);

    expect($data['APP_KEY'])->toBe('base64:abc123==')
        ->and($data['DB_PASSWORD'])->toBe('secret');
});

// YAML export
it('exports to YAML format', function () {
    $output = $this->formatter->toYaml($this->vars);

    expect($output)->toContain('APP_NAME')
        ->and($output)->toContain('Laravel');
});

it('masks sensitive values in YAML export by default', function () {
    $output = $this->formatter->toYaml($this->vars);

    expect($output)->not->toContain('base64:abc123==');
});

// Round-trip: toEnv then parse back
it('env export round-trips cleanly', function () {
    $parser   = new EnvParser;
    $exported = $this->formatter->toEnv($this->vars, reveal: true);
    $lines    = $parser->parseContents($exported);
    $map      = $parser->toKeyMap($lines);

    foreach (['APP_NAME', 'APP_ENV'] as $key) {
        expect($map[$key]->value)->toBe($this->vars[$key]);
    }
});

it('quotes values containing spaces in .env output', function () {
    $vars   = ['APP_DESC' => 'My Cool App'];
    $output = $this->formatter->toEnv($vars, reveal: true);

    expect($output)->toContain('"My Cool App"');
});
