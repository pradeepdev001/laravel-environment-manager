# Laravel Environment Manager

[![Tests](https://github.com/pradeepdev001/laravel-environment-manager/actions/workflows/tests.yml/badge.svg)](https://github.com/pradeepdev001/laravel-environment-manager/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/pradeepdev001/laravel-environment-manager/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/pradeepdev001/laravel-environment-manager/actions/workflows/static-analysis.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/pradeepdev001/laravel-environment-manager.svg)](https://packagist.org/packages/pradeepdev001/laravel-environment-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/pradeepdev001/laravel-environment-manager.svg)](https://packagist.org/packages/pradeepdev001/laravel-environment-manager)
[![License](https://img.shields.io/packagist/l/pradeepdev001/laravel-environment-manager.svg)](LICENSE)

A secure, enterprise-grade Laravel package to manage `.env` variables through a web-based admin UI and Artisan commands. Built for teams who need visibility, control, and auditability over their application configuration.

---

## Features

- **Viewer** — browse all env variables grouped by category with search, filter, and sort
- **Editor** — add, update, delete, rename, and bulk edit variables with atomic file writes
- **Sensitive value masking** — auto-detects and masks `APP_KEY`, `*_PASSWORD`, `*_SECRET`, `STRIPE_*`, etc.
- **Validation** — built-in rules for `APP_URL`, `MAIL_PORT`, `DB_CONNECTION`, `CACHE_DRIVER`, and more
- **Backup & restore** — automatic pre-change backups, manual backups, encrypted backups, restore from UI or CLI
- **Version history** — full audit trail of every change with user, IP, reason, and rollback support
- **Diff viewer** — compare any two snapshots side-by-side
- **Import / Export** — `.env`, JSON, and YAML formats with pre-import validation
- **Environment comparison** — compare Local, Staging, Production side-by-side
- **Cache management** — auto-runs `config:clear`, `config:cache`, etc. after every save
- **REST API** — full CRUD API with Sanctum auth and rate limiting
- **Artisan commands** — `list`, `get`, `set`, `delete`, `backup`, `restore`, `compare`, `validate`
- **Role-based authorization** — Super Admin, Admin, Read Only with configurable permissions
- **Audit log** — records every action with user, IP, browser, and timestamp
- **Notifications** — Mail, Slack, Teams, and custom webhooks
- **Dark mode** — responsive Blade UI with OS-level dark mode support

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.1 |
| Laravel | ^8.0 \| ^9.0 \| ^10.0 \| ^11.0 \| ^12.0 |

---

## Installation

```bash
composer require pradeepdev001/laravel-environment-manager
```

Publish the config file:

```bash
php artisan vendor:publish --tag=environment-manager-config
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=environment-manager-migrations
php artisan migrate
```

---

## Quick Start

After installation, visit `/admin/env-manager` in your browser (requires authentication via the configured guard).

```bash
# List all variables
php artisan env-manager:list

# Set a variable
php artisan env-manager:set APP_NAME "My App" --reason="Rebranding"

# Get a variable
php artisan env-manager:get DB_CONNECTION

# Create a backup
php artisan env-manager:backup

# Validate the current .env
php artisan env-manager:validate
```

### Using the Facade

```php
use Pradeepdev\EnvironmentManager\Facades\EnvManager;

// Get all variables
$variables = EnvManager::all();

// Get a single variable
$var = EnvManager::get('APP_NAME');

// Set a variable (validates, backs up, records history)
EnvManager::set('APP_NAME', 'New Name', reason: 'Rebranding');

// Bulk update
EnvManager::bulkSet([
    'CACHE_DRIVER'     => 'redis',
    'QUEUE_CONNECTION' => 'redis',
], reason: 'Switch to Redis');

// Delete
EnvManager::delete('OLD_KEY');
```

---

## Configuration

After publishing, edit `config/environment-manager.php`:

```php
return [
    // Enable/disable web UI and REST API
    'enable_ui'  => true,
    'enable_api' => true,

    // Admin-first defaults
    'guard'            => 'admin',
    'route_prefix'     => 'admin/env-manager',
    'route_middleware' => null, // defaults to ['web', 'auth:admin']

    // Restrict to specific user IDs (null = all authenticated users)
    'allowed_users' => [1, 2],

    // Auto-backup before every change
    'backup_path'       => storage_path('env-backups'),
    'backup_retention'  => 20,
    'backup_encryption' => false,

    // Custom sensitivity patterns
    'masking_patterns' => ['MY_CUSTOM_*'],

    // Cache commands to run after save
    'cache_commands' => ['config:clear', 'config:cache'],

    // Notifications
    'notifications' => [
        'on_env_update' => true,
        'slack' => [
            'enabled'     => true,
            'webhook_url' => env('ENV_MANAGER_SLACK_WEBHOOK'),
        ],
    ],
];
```

See [`config/environment-manager.php`](config/environment-manager.php) for all available options.

---

## REST API

All endpoints are under `/api/env-manager` and, by default, require the `admin` guard.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/env-manager/env` | List all variables |
| POST | `/api/env-manager/env` | Create a variable |
| PUT | `/api/env-manager/env/{key}` | Update a variable |
| DELETE | `/api/env-manager/env/{key}` | Delete a variable |
| GET | `/api/env-manager/env/history` | Version history |
| GET | `/api/env-manager/env/backups` | List backups |

---

## Authorization

The package uses a role-based system out of the box.

| Permission | Super Admin | Admin | Read Only |
|---|:---:|:---:|:---:|
| view-env | ✅ | ✅ | ✅ |
| edit-env | ✅ | ✅ | ❌ |
| delete-env | ✅ | ✅ | ❌ |
| backup-env | ✅ | ✅ | ❌ |
| restore-env | ✅ | ✅ | ❌ |
| reveal-secrets | ✅ | configurable | ❌ |

Plug in your own permission system via the `authorization_callback` config key:

```php
'authorization_callback' => function ($user, $permission) {
    return $user->hasPermissionTo($permission); // e.g. Spatie Permission
},
```

---

## Testing

```bash
composer test
composer test-coverage
```

---

## Security

Sensitive values are **never** stored, logged, or returned in plaintext. See [SECURITY.md](SECURITY.md) for the responsible disclosure process.

---

## License

MIT — see [LICENSE](LICENSE).
