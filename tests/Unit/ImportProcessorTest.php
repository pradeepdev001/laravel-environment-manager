<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;
use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\ImportProcessor;
use Pradeepdev\EnvironmentManager\Services\ValidationEngine;

beforeEach(function () {
    $this->processor = new ImportProcessor(new EnvParser(), new ValidationEngine());
});

// .env format import
it('parses a valid .env string', function () {
    $content = "APP_NAME=Laravel\nAPP_ENV=local\n";
    $result  = $this->processor->parseEnvString($content);

    expect($result)->toHaveKeys(['APP_NAME', 'APP_ENV'])
        ->and($result['APP_NAME'])->toBe('Laravel')
        ->and($result['APP_ENV'])->toBe('local');
});

it('parses .env string with comments and blanks', function () {
    $content = "# App config\nAPP_NAME=Test\n\nAPP_ENV=local\n";
    $result  = $this->processor->parseEnvString($content);

    expect($result)->toHaveKeys(['APP_NAME', 'APP_ENV']);
});

// JSON format import
it('parses a valid JSON string', function () {
    $json   = json_encode(['APP_NAME' => 'Laravel', 'APP_ENV' => 'local']);
    $result = $this->processor->parseJsonString($json);

    expect($result)->toHaveKeys(['APP_NAME', 'APP_ENV'])
        ->and($result['APP_NAME'])->toBe('Laravel');
});

it('throws on invalid JSON', function () {
    $this->processor->parseJsonString('not-json');
})->throws(\InvalidArgumentException::class);

it('throws on JSON array instead of object', function () {
    $this->processor->parseJsonString('["APP_NAME", "Laravel"]');
})->throws(\InvalidArgumentException::class);

// Validation in processEnv
it('processes valid .env and returns variables', function () {
    $content = "APP_ENV=local\nDB_CONNECTION=mysql\n";
    $result  = $this->processor->processEnv($content);

    expect($result)->toHaveKeys(['APP_ENV', 'DB_CONNECTION']);
});

it('throws ValidationException for invalid values in processEnv', function () {
    $content = "APP_URL=not-a-url\n";
    $this->processor->processEnv($content);
})->throws(ValidationException::class);

// Validation in processJson
it('processes valid JSON and returns variables', function () {
    $json   = json_encode(['APP_ENV' => 'local']);
    $result = $this->processor->processJson($json);

    expect($result)->toHaveKey('APP_ENV');
});

it('throws ValidationException for invalid values in processJson', function () {
    $json = json_encode(['DB_CONNECTION' => 'oracle']);
    $this->processor->processJson($json);
})->throws(ValidationException::class);

// validate rejects entire payload on any error
it('validates rejects entire payload when one key fails', function () {
    $this->processor->validate([
        'APP_ENV'  => 'local',     // valid
        'APP_URL'  => 'bad-url',   // invalid
    ]);
})->throws(ValidationException::class);

// Round-trip: import .env then export .env produces equivalent records
it('round-trips: parse .env then re-export produces equivalent keys', function () {
    $content  = "APP_NAME=Laravel\nDB_CONNECTION=mysql\nMAIL_PORT=587\n";
    $imported = $this->processor->parseEnvString($content);

    $formatter = new \Pradeepdev\EnvironmentManager\Services\ExportFormatter(
        new \Pradeepdev\EnvironmentManager\Services\SensitivityDetector()
    );
    $exported  = $formatter->toEnv($imported, reveal: true);
    $reimported = $this->processor->parseEnvString($exported);

    foreach (array_keys($imported) as $key) {
        expect($reimported)->toHaveKey($key);
        expect($reimported[$key])->toBe($imported[$key]);
    }
});
