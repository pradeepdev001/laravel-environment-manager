<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Services\DiffEngine;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;

beforeEach(function () {
    $this->engine   = new DiffEngine;
    $this->detector = new SensitivityDetector;
});

it('detects added keys', function () {
    $old  = ['APP_NAME' => 'Laravel'];
    $new  = ['APP_NAME' => 'Laravel', 'APP_ENV' => 'local'];
    $diff = $this->engine->diff($old, $new);

    expect($diff['APP_ENV']['status'])->toBe(DiffEngine::STATUS_ADDED)
        ->and($diff['APP_ENV']['old'])->toBeNull()
        ->and($diff['APP_ENV']['new'])->toBe('local');
});

it('detects removed keys', function () {
    $old  = ['APP_NAME' => 'Laravel', 'OLD_KEY' => 'old'];
    $new  = ['APP_NAME' => 'Laravel'];
    $diff = $this->engine->diff($old, $new);

    expect($diff['OLD_KEY']['status'])->toBe(DiffEngine::STATUS_REMOVED)
        ->and($diff['OLD_KEY']['old'])->toBe('old')
        ->and($diff['OLD_KEY']['new'])->toBeNull();
});

it('detects modified values', function () {
    $old  = ['APP_NAME' => 'Laravel'];
    $new  = ['APP_NAME' => 'Updated'];
    $diff = $this->engine->diff($old, $new);

    expect($diff['APP_NAME']['status'])->toBe(DiffEngine::STATUS_MODIFIED)
        ->and($diff['APP_NAME']['old'])->toBe('Laravel')
        ->and($diff['APP_NAME']['new'])->toBe('Updated');
});

it('marks unchanged keys as unchanged', function () {
    $old  = ['APP_NAME' => 'Laravel'];
    $new  = ['APP_NAME' => 'Laravel'];
    $diff = $this->engine->diff($old, $new);

    expect($diff['APP_NAME']['status'])->toBe(DiffEngine::STATUS_UNCHANGED);
});

it('returns empty changesOnly for identical maps', function () {
    $map  = ['APP_NAME' => 'Laravel', 'APP_ENV' => 'local'];
    $diff = $this->engine->changesOnly($map, $map);

    expect($diff)->toBeEmpty();
});

it('isIdentical returns true for identical maps', function () {
    $map = ['A' => '1', 'B' => '2'];
    expect($this->engine->isIdentical($map, $map))->toBeTrue();
});

it('isIdentical returns false for different maps', function () {
    $a = ['A' => '1'];
    $b = ['A' => '2'];
    expect($this->engine->isIdentical($a, $b))->toBeFalse();
});

it('masks sensitive values in diff', function () {
    $old  = ['APP_KEY' => 'real-key', 'APP_NAME' => 'Laravel'];
    $new  = ['APP_KEY' => 'new-key', 'APP_NAME' => 'Updated'];
    $diff = $this->engine->diff($old, $new);
    $diff = $this->engine->maskSensitive($diff, $this->detector);

    expect($diff['APP_KEY']['old'])->toBe('••••••••')
        ->and($diff['APP_KEY']['new'])->toBe('••••••••')
        ->and($diff['APP_NAME']['old'])->toBe('Laravel')
        ->and($diff['APP_NAME']['new'])->toBe('Updated');
});

it('handles empty maps', function () {
    expect($this->engine->diff([], []))->toBeEmpty();
    expect($this->engine->diff([], ['A' => '1']))->toHaveKey('A');
    expect($this->engine->diff(['A' => '1'], []))->toHaveKey('A');
});

it('sorts keys alphabetically', function () {
    $old  = ['Z' => '1', 'A' => '2'];
    $new  = ['Z' => '1', 'A' => '2'];
    $diff = $this->engine->diff($old, $new);

    expect(array_keys($diff))->toEqual(['A', 'Z']);
});
