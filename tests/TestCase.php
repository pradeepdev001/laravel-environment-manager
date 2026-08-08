<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Tests;

use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\TestCase as Orchestra;
use Pradeepdev\EnvironmentManager\EnvironmentManagerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EnvironmentManagerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('auth.defaults.guard', 'admin');
        $app['config']->set('auth.guards.admin', [
            'driver'   => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model'  => User::class,
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('environment-manager.env_file_path', $this->getTestEnvPath());
        $app['config']->set('environment-manager.backup_path', sys_get_temp_dir().'/env-manager-test-backups');
        $app['config']->set('environment-manager.cache_after_save', false);
        $app['config']->set('environment-manager.backup_retention', 5);
        $app['config']->set('environment-manager.guard', 'admin');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function tearDown(): void
    {
        // Clean up test .env and backup files
        $envPath = $this->getTestEnvPath();
        if (file_exists($envPath)) {
            @unlink($envPath);
        }

        $backupPath = sys_get_temp_dir().'/env-manager-test-backups';
        if (is_dir($backupPath)) {
            array_map('unlink', glob($backupPath.'/*') ?: []);
            @rmdir($backupPath);
        }

        parent::tearDown();
    }

    protected function getTestEnvPath(): string
    {
        return sys_get_temp_dir().'/env-manager-test-'.getmypid().'.env';
    }

    protected function writeTestEnv(string $contents): void
    {
        file_put_contents($this->getTestEnvPath(), $contents);
    }

    protected function readTestEnv(): string
    {
        return file_get_contents($this->getTestEnvPath()) ?: '';
    }
}
