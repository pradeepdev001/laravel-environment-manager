# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2024-01-01

### Added
- Initial release of `pradeepdev001/laravel-environment-manager`
- Environment variable viewer with category grouping, search, sort, and filtering
- Secure editor: add, update, delete, rename, bulk edit with atomic writes and file locking
- Sensitive value auto-detection and masking (APP_KEY, *_PASSWORD, *_SECRET, STRIPE_*, etc.)
- Configurable reveal-secrets permission for authorized users
- Validation engine with built-in rules for APP_URL, MAIL_PORT, DB_CONNECTION, CACHE_DRIVER, QUEUE_CONNECTION, SESSION_DRIVER, and more
- Auto-category grouping: Application, Database, Mail, Cache, Queue, Broadcast, Filesystem, Redis, AWS, Stripe, Custom
- Automatic pre-change backups with manual backup, scheduled backup, restore, download, delete
- Backup encryption with AES-256-CBC
- Full version history with user, timestamp, IP, reason, and rollback support
- Diff viewer between any two snapshots
- Import from `.env` and JSON formats with pre-import validation
- Export to `.env`, JSON, and YAML formats
- Environment comparison across Local, Development, Staging, Production
- Cache management: configurable Artisan commands run after every save
- 8 Artisan commands: `env-manager:list`, `get`, `set`, `delete`, `backup`, `restore`, `compare`, `validate`
- Prune command: `env-manager:prune` for retention-based cleanup
- Role-based access control: Super Admin, Admin, Read Only with configurable permissions
- Audit log recording every action with user, IP, browser, timestamp
- Notifications via Mail, Slack, Microsoft Teams, and custom webhooks
- Responsive Blade UI with dark mode support (Tailwind CSS CDN)
- REST API with rate limiting, Sanctum authentication, and JSON responses
- Comprehensive Pest test suite
- Full documentation in `docs/`
- GitHub Actions CI/CD workflows
- PHPStan level 8, Laravel Pint code style enforcement
