<?php

declare(strict_types=1);

beforeEach(function () {
    $this->writeTestEnv("APP_NAME=Laravel\nAPP_ENV=local\nDB_PASSWORD=secret\nDB_CONNECTION=mysql\n");
});

// env-manager:list
it('env-manager:list displays all variables', function () {
    $this->artisan('env-manager:list')
        ->assertSuccessful()
        ->expectsOutputToContain('APP_NAME');
});

it('env-manager:list filters by category', function () {
    $this->artisan('env-manager:list', ['--category' => 'Database'])
        ->assertSuccessful()
        ->expectsOutputToContain('DB_CONNECTION');
});

it('env-manager:list filters by search', function () {
    $this->artisan('env-manager:list', ['--search' => 'APP'])
        ->assertSuccessful()
        ->expectsOutputToContain('APP_NAME');
});

it('env-manager:list outputs json format', function () {
    $this->artisan('env-manager:list', ['--format' => 'json'])
        ->assertSuccessful();
});

// env-manager:get
it('env-manager:get displays a variable', function () {
    $this->artisan('env-manager:get', ['key' => 'APP_NAME'])
        ->assertSuccessful()
        ->expectsOutputToContain('APP_NAME');
});

it('env-manager:get masks sensitive variable', function () {
    $this->artisan('env-manager:get', ['key' => 'DB_PASSWORD'])
        ->assertSuccessful()
        ->expectsOutputToContain('••••••••');
});

it('env-manager:get fails for missing key', function () {
    $this->artisan('env-manager:get', ['key' => 'NONEXISTENT'])
        ->assertFailed();
});

// env-manager:set
it('env-manager:set creates a new variable', function () {
    $this->artisan('env-manager:set', ['key' => 'TEST_VAR', 'value' => 'test_value'])
        ->assertSuccessful();

    expect(app(\Pradeepdev\EnvironmentManager\EnvManager::class)->get('TEST_VAR')?->rawValue)
        ->toBe('test_value');
});

it('env-manager:set updates an existing variable', function () {
    $this->artisan('env-manager:set', ['key' => 'APP_NAME', 'value' => 'Updated'])
        ->assertSuccessful();

    expect(app(\Pradeepdev\EnvironmentManager\EnvManager::class)->get('APP_NAME')?->rawValue)
        ->toBe('Updated');
});

it('env-manager:set fails for invalid value', function () {
    $this->artisan('env-manager:set', ['key' => 'APP_URL', 'value' => 'not-a-url'])
        ->assertFailed();
});

it('env-manager:set accepts --reason option', function () {
    $this->artisan('env-manager:set', [
        'key'      => 'APP_NAME',
        'value'    => 'Reasoned',
        '--reason' => 'Testing reason flag',
    ])->assertSuccessful();
});

// env-manager:delete
it('env-manager:delete removes a variable with --force', function () {
    $this->artisan('env-manager:delete', ['key' => 'APP_ENV', '--force' => true])
        ->assertSuccessful();

    expect(app(\Pradeepdev\EnvironmentManager\EnvManager::class)->get('APP_ENV'))->toBeNull();
});

it('env-manager:delete fails for missing key', function () {
    $this->artisan('env-manager:delete', ['key' => 'NONEXISTENT', '--force' => true])
        ->assertFailed();
});

// env-manager:backup
it('env-manager:backup creates a backup file', function () {
    $this->artisan('env-manager:backup')
        ->assertSuccessful()
        ->expectsOutputToContain('Backup created');
});

// env-manager:validate
it('env-manager:validate passes for valid env', function () {
    $this->artisan('env-manager:validate')
        ->assertSuccessful();
});

it('env-manager:validate fails for invalid env', function () {
    $this->writeTestEnv("APP_URL=not-a-url\nDB_CONNECTION=oracle\n");

    $this->artisan('env-manager:validate')
        ->assertFailed();
});

// env-manager:compare
it('env-manager:compare shows differences between two files', function () {
    $file1 = sys_get_temp_dir() . '/env_compare_a_' . getmypid() . '.env';
    $file2 = sys_get_temp_dir() . '/env_compare_b_' . getmypid() . '.env';

    file_put_contents($file1, "APP_NAME=Laravel\nNEW_KEY=value\n");
    file_put_contents($file2, "APP_NAME=Changed\n");

    $this->artisan('env-manager:compare', ['env1' => $file1, 'env2' => $file2])
        ->assertSuccessful();

    @unlink($file1);
    @unlink($file2);
});

it('env-manager:compare reports identical files', function () {
    $file1 = sys_get_temp_dir() . '/env_same_a_' . getmypid() . '.env';
    $file2 = sys_get_temp_dir() . '/env_same_b_' . getmypid() . '.env';

    file_put_contents($file1, "APP_NAME=Laravel\n");
    file_put_contents($file2, "APP_NAME=Laravel\n");

    $this->artisan('env-manager:compare', ['env1' => $file1, 'env2' => $file2])
        ->assertSuccessful()
        ->expectsOutputToContain('identical');

    @unlink($file1);
    @unlink($file2);
});
