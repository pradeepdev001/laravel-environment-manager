<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Exceptions\ValidationException;

beforeEach(function () {
    $this->writeTestEnv("APP_NAME=Laravel\nAPP_ENV=local\nDB_PASSWORD=secret\n");
    $this->manager = app(EnvManager::class);
});

it('reads all variables', function () {
    $vars = $this->manager->all();

    expect($vars)->toHaveCount(3)
        ->and($vars->pluck('key')->toArray())->toContain('APP_NAME', 'APP_ENV', 'DB_PASSWORD');
});

it('gets a single variable', function () {
    $var = $this->manager->get('APP_NAME');

    expect($var)->not->toBeNull()
        ->and($var->rawValue)->toBe('Laravel');
});

it('returns null for missing variable', function () {
    expect($this->manager->get('NONEXISTENT'))->toBeNull();
});

it('sets a new variable', function () {
    $this->manager->set('NEW_KEY', 'new_value');

    expect($this->manager->get('NEW_KEY')?->rawValue)->toBe('new_value');
});

it('updates an existing variable', function () {
    $this->manager->set('APP_NAME', 'Updated');

    expect($this->manager->get('APP_NAME')?->rawValue)->toBe('Updated');
});

it('deletes a variable', function () {
    $this->manager->delete('APP_ENV');

    expect($this->manager->get('APP_ENV'))->toBeNull();
});

it('renames a variable', function () {
    $this->manager->rename('APP_NAME', 'APPLICATION_NAME');

    expect($this->manager->get('APP_NAME'))->toBeNull();
    expect($this->manager->get('APPLICATION_NAME')?->rawValue)->toBe('Laravel');
});

it('bulk sets multiple variables', function () {
    $this->manager->bulkSet([
        'BULK_A' => 'value_a',
        'BULK_B' => 'value_b',
    ]);

    expect($this->manager->get('BULK_A')?->rawValue)->toBe('value_a');
    expect($this->manager->get('BULK_B')?->rawValue)->toBe('value_b');
});

it('returns key-value map', function () {
    $map = $this->manager->toKeyValueMap();

    expect($map)->toHaveKey('APP_NAME')
        ->and($map['APP_NAME'])->toBe('Laravel');
});

it('detects sensitive variables correctly', function () {
    $var = $this->manager->get('DB_PASSWORD');

    expect($var->sensitive)->toBeTrue();
});

it('detects non-sensitive variables correctly', function () {
    $var = $this->manager->get('APP_NAME');

    expect($var->sensitive)->toBeFalse();
});

it('throws ValidationException for invalid value', function () {
    $this->manager->set('APP_URL', 'not-a-url');
})->throws(ValidationException::class);

it('throws ValidationException for bulk set with invalid value', function () {
    $this->manager->bulkSet(['DB_CONNECTION' => 'oracle']);
})->throws(ValidationException::class);

it('throws for invalid key format', function () {
    $this->manager->set('invalid key', 'value');
})->throws(InvalidArgumentException::class);

it('creates backup before every write', function () {
    $backupDir = config('environment-manager.backup_path');
    $this->manager->set('NEW_VAR', 'test');

    $backups = glob($backupDir.'/env_backup_*.env*');
    expect($backups)->not->toBeEmpty();
});

it('assigns correct categories to variables', function () {
    $this->writeTestEnv("APP_NAME=Test\nDB_HOST=localhost\nMAIL_HOST=smtp.test\nREDIS_HOST=127.0.0.1\n");

    $vars = $this->manager->all()->keyBy('key');

    expect($vars['APP_NAME']->category)->toBe('Application');
    expect($vars['DB_HOST']->category)->toBe('Database');
    expect($vars['MAIL_HOST']->category)->toBe('Mail');
    expect($vars['REDIS_HOST']->category)->toBe('Redis');
});
