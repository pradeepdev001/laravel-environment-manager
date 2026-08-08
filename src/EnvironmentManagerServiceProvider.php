<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager;

use Illuminate\Support\ServiceProvider;
use Pradeepdev\EnvironmentManager\Commands\BackupCommand;
use Pradeepdev\EnvironmentManager\Commands\CompareCommand;
use Pradeepdev\EnvironmentManager\Commands\DeleteCommand;
use Pradeepdev\EnvironmentManager\Commands\GetCommand;
use Pradeepdev\EnvironmentManager\Commands\ListCommand;
use Pradeepdev\EnvironmentManager\Commands\PruneCommand;
use Pradeepdev\EnvironmentManager\Commands\RestoreCommand;
use Pradeepdev\EnvironmentManager\Commands\SetCommand;
use Pradeepdev\EnvironmentManager\Commands\ValidateCommand;
use Pradeepdev\EnvironmentManager\Services\AuditLogger;
use Pradeepdev\EnvironmentManager\Services\BackupManager;
use Pradeepdev\EnvironmentManager\Services\CacheManager;
use Pradeepdev\EnvironmentManager\Services\DiffEngine;
use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\EnvWriter;
use Pradeepdev\EnvironmentManager\Services\ExportFormatter;
use Pradeepdev\EnvironmentManager\Services\ImportProcessor;
use Pradeepdev\EnvironmentManager\Services\NotificationDispatcher;
use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;
use Pradeepdev\EnvironmentManager\Services\ValidationEngine;
use Pradeepdev\EnvironmentManager\Services\VersionHistory;

class EnvironmentManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/environment-manager.php',
            'environment-manager',
        );

        $this->app->singleton(SensitivityDetector::class, function ($app) {
            return new SensitivityDetector(
                config('environment-manager.masking_patterns', []),
            );
        });

        $this->app->singleton(EnvParser::class, function ($app) {
            return new EnvParser;
        });

        $this->app->singleton(EnvWriter::class, function ($app) {
            return new EnvWriter;
        });

        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger;
        });

        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager(
                config('environment-manager.cache_commands', []),
                config('environment-manager.cache_after_save', true),
            );
        });

        $this->app->singleton(BackupManager::class, function ($app) {
            return new BackupManager(
                config('environment-manager.backup_path', storage_path('env-backups')),
                config('environment-manager.backup_retention', 20),
                config('environment-manager.backup_encryption', false),
            );
        });

        $this->app->singleton(ValidationEngine::class, function ($app) {
            return new ValidationEngine(
                config('environment-manager.validation_rules', []),
            );
        });

        $this->app->singleton(VersionHistory::class, function ($app) {
            return new VersionHistory(
                $app->make(AuditLogger::class),
            );
        });

        $this->app->singleton(DiffEngine::class, function ($app) {
            return new DiffEngine;
        });

        $this->app->singleton(ExportFormatter::class, function ($app) {
            return new ExportFormatter(
                $app->make(SensitivityDetector::class),
            );
        });

        $this->app->singleton(ImportProcessor::class, function ($app) {
            return new ImportProcessor(
                $app->make(EnvParser::class),
                $app->make(ValidationEngine::class),
            );
        });

        $this->app->singleton(NotificationDispatcher::class, function ($app) {
            return new NotificationDispatcher(
                config('environment-manager.notifications', []),
            );
        });

        $this->app->singleton(EnvManager::class, function ($app) {
            return new EnvManager(
                $app->make(EnvParser::class),
                $app->make(EnvWriter::class),
                $app->make(BackupManager::class),
                $app->make(VersionHistory::class),
                $app->make(CacheManager::class),
                $app->make(ValidationEngine::class),
                $app->make(SensitivityDetector::class),
                $app->make(AuditLogger::class),
                $app->make(NotificationDispatcher::class),
            );
        });

        $this->app->alias(EnvManager::class, 'env-manager');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishAssets();
            $this->registerCommands();
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (config('environment-manager.enable_ui', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'environment-manager');
        }

        if (config('environment-manager.enable_api', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }

    protected function publishAssets(): void
    {
        $this->publishes([
            __DIR__.'/../config/environment-manager.php' => config_path('environment-manager.php'),
        ], 'environment-manager-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'environment-manager-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/environment-manager'),
        ], 'environment-manager-views');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/environment-manager'),
        ], 'environment-manager-assets');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            ListCommand::class,
            GetCommand::class,
            SetCommand::class,
            DeleteCommand::class,
            BackupCommand::class,
            RestoreCommand::class,
            CompareCommand::class,
            ValidateCommand::class,
            PruneCommand::class,
        ]);
    }
}
