<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;
use Pradeepdev\EnvironmentManager\Services\ValidationEngine;

beforeEach(function () {
    $this->validator = new ValidationEngine;
});

// URL validation
it('passes valid APP_URL', function () {
    expect($this->validator->validateOne('APP_URL', 'https://example.com'))->toBeEmpty();
});

it('fails invalid APP_URL', function () {
    expect($this->validator->validateOne('APP_URL', 'not-a-url'))->not->toBeEmpty();
});

// Integer validation
it('passes valid MAIL_PORT', function () {
    expect($this->validator->validateOne('MAIL_PORT', '587'))->toBeEmpty();
});

it('fails string MAIL_PORT', function () {
    expect($this->validator->validateOne('MAIL_PORT', 'smtp'))->not->toBeEmpty();
});

it('fails MAIL_PORT below min', function () {
    expect($this->validator->validateOne('MAIL_PORT', '0'))->not->toBeEmpty();
});

it('fails MAIL_PORT above max', function () {
    expect($this->validator->validateOne('MAIL_PORT', '99999'))->not->toBeEmpty();
});

// Enum validation
it('passes valid DB_CONNECTION', function () {
    foreach (['mysql', 'pgsql', 'sqlite', 'sqlsrv', 'mariadb'] as $driver) {
        expect($this->validator->validateOne('DB_CONNECTION', $driver))->toBeEmpty();
    }
});

it('fails invalid DB_CONNECTION', function () {
    expect($this->validator->validateOne('DB_CONNECTION', 'oracle'))->not->toBeEmpty();
});

it('passes valid CACHE_DRIVER', function () {
    expect($this->validator->validateOne('CACHE_DRIVER', 'redis'))->toBeEmpty();
});

it('fails invalid CACHE_DRIVER', function () {
    expect($this->validator->validateOne('CACHE_DRIVER', 'turbo'))->not->toBeEmpty();
});

it('passes valid SESSION_DRIVER', function () {
    expect($this->validator->validateOne('SESSION_DRIVER', 'file'))->toBeEmpty();
});

it('passes valid QUEUE_CONNECTION', function () {
    expect($this->validator->validateOne('QUEUE_CONNECTION', 'redis'))->toBeEmpty();
});

// Boolean validation
it('passes valid APP_DEBUG values', function () {
    foreach (['true', 'false', '1', '0', 'yes', 'no'] as $value) {
        expect($this->validator->validateOne('APP_DEBUG', $value))->toBeEmpty();
    }
});

it('fails invalid APP_DEBUG', function () {
    expect($this->validator->validateOne('APP_DEBUG', 'enabled'))->not->toBeEmpty();
});

// Required rule
it('fails empty required variable', function () {
    $validator = new ValidationEngine(['MY_KEY' => ['required']]);
    expect($validator->validateOne('MY_KEY', ''))->not->toBeEmpty();
});

// Nullable rule
it('passes empty nullable variable', function () {
    $validator = new ValidationEngine(['MY_KEY' => ['nullable']]);
    expect($validator->validateOne('MY_KEY', ''))->toBeEmpty();
});

// Custom regex rule
it('passes valid regex rule', function () {
    $validator = new ValidationEngine(['PORT' => ['regex:/^\d+$/']]);
    expect($validator->validateOne('PORT', '8080'))->toBeEmpty();
});

it('fails invalid regex rule', function () {
    $validator = new ValidationEngine(['PORT' => ['regex:/^\d+$/']]);
    expect($validator->validateOne('PORT', 'abc'))->not->toBeEmpty();
});

// validateOrFail
it('throws ValidationException on failure', function () {
    $this->validator->validateOrFail(['APP_URL' => 'not-a-url']);
})->throws(ValidationException::class);

it('does not throw when all valid', function () {
    $this->validator->validateOrFail(['APP_URL' => 'https://example.com']);
    expect(true)->toBeTrue(); // no exception
});

// validateMany
it('returns errors for all failing keys', function () {
    $errors = $this->validator->validateMany([
        'APP_URL'       => 'bad-url',
        'DB_CONNECTION' => 'oracle',
    ]);

    expect($errors)->toHaveKey('APP_URL')
        ->and($errors)->toHaveKey('DB_CONNECTION');
});

// Unknown key passes with no rules
it('passes unknown keys with no rules', function () {
    expect($this->validator->validateOne('CUSTOM_THING', 'anything'))->toBeEmpty();
});
