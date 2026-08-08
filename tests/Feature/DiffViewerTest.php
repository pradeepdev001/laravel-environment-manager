<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Pradeepdev\EnvironmentManager\EnvManager;
use Pradeepdev\EnvironmentManager\Services\BackupManager;

beforeEach(function () {
    $this->writeTestEnv("APP_NAME=Laravel\nAPP_ENV=local\nDB_PASSWORD=secret\n");

    config(['environment-manager.bypass_auth_in_local' => true]);
    config(['app.env' => 'local']);

    $this->user = User::forceCreate([
        'name'     => 'Admin User',
        'email'    => uniqid('admin').'@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('shows differences between current env and a backup', function () {
    /** @var BackupManager $backupManager */
    $backupManager = app(BackupManager::class);
    $backupPath    = $backupManager->create(app(EnvManager::class)->getEnvPath());

    app(EnvManager::class)->set('APP_NAME', 'Updated Laravel');

    $this->actingAs($this->user, 'admin')
        ->get('/'.config('environment-manager.route_prefix').'/diff?source_a=current&source_b=backup:'.basename($backupPath))
        ->assertOk()
        ->assertSee('APP_NAME')
        ->assertSee('MODIFIED')
        ->assertSee('Updated Laravel')
        ->assertSee('Laravel');
});

it('shows identical state when comparing the same source', function () {
    $this->actingAs($this->user, 'admin')
        ->get('/'.config('environment-manager.route_prefix').'/diff?source_a=current&source_b=current')
        ->assertOk()
        ->assertSee('The selected sources are identical.');
});
