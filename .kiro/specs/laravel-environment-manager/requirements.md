# Requirements Document

## Introduction

`pradeepdev001/laravel-environment-manager` is a production-ready, enterprise-grade Laravel package that provides a secure, feature-rich interface for managing application environment variables (`.env` files) through a web-based admin UI and Artisan commands. The package targets Laravel 10.x, 11.x, and 12.x with PHP 8.1+, and supports MySQL, PostgreSQL, SQLite, and MariaDB. It is designed to be installable via Composer, published through Packagist, and maintained as an open-source project under a standard OSS governance model. Development follows a milestone-by-milestone approach (Milestone 0 through 13), with each milestone being independently releasable and semantically versioned.

---

## Glossary

- **EnvManager**: The top-level Laravel service that orchestrates reading, writing, and managing `.env` files.
- **EnvParser**: The component responsible for parsing `.env` files into structured representations while preserving comments and formatting.
- **EnvWriter**: The component responsible for atomically writing structured data back to `.env` files while preserving formatting and comments.
- **EnvVariable**: A single key-value pair parsed from the `.env` file, including metadata (type, category, description, sensitivity flag).
- **BackupManager**: The component responsible for creating, listing, restoring, downloading, and deleting `.env` backups.
- **VersionHistory**: The audit trail of all changes made to environment variables, including user, timestamp, old value, new value, reason, and IP address.
- **DiffEngine**: The component that computes and renders the diff between two `.env` versions or two environment snapshots.
- **AuditLogger**: The component that records every user action on environment variables to persistent storage.
- **AuthorizationGate**: The Laravel Gate/Policy layer that enforces role-based access control for all package operations.
- **SensitivityDetector**: The component that determines whether an `EnvVariable` key is sensitive based on configurable patterns.
- **CacheManager**: The component that runs configured Laravel cache commands after environment changes.
- **NotificationDispatcher**: The component that sends notifications to configured channels when significant environment changes occur.
- **ExportFormatter**: The component that serializes environment data into `.env`, JSON, or YAML formats.
- **ImportProcessor**: The component that ingests `.env` or JSON files, validates them, and applies changes.
- **ApiController**: The HTTP controller exposing the REST API endpoints.
- **UiController**: The HTTP controller serving the Blade-based admin interface.
- **Super_Admin**: A user role with full access to all package features including revealing secrets and restoring backups.
- **Admin**: A user role with access to view, edit, backup, and restore but with configurable secret-reveal permission.
- **Read_Only**: A user role with access to view environment variables only, with secrets always masked.
- **Sensitive_Key**: An `EnvVariable` whose key matches a configured sensitivity pattern (e.g., APP_KEY, DB_PASSWORD).
- **Atomic_Write**: A file write strategy that writes to a temporary file then atomically renames it to prevent partial writes.
- **File_Lock**: A mutual-exclusion lock on the `.env` file to prevent concurrent write corruption.
- **Category**: A logical grouping of `EnvVariable` records (Application, Database, Mail, Cache, Queue, Broadcast, Filesystem, Redis, AWS, Stripe, Custom).
- **Backup_File**: An encrypted or plaintext snapshot of the `.env` file stored in the configured backup path.
- **Changelog**: The `CHANGELOG.md` file tracking all released versions and changes.
- **PestTest**: A test case written using the Pest PHP testing framework.

---

## Requirements

### Requirement 1: Project Architecture and Package Setup

**User Story:** As a package maintainer, I want a well-structured Composer package scaffold, so that the package can be installed, configured, and extended following Laravel conventions.

#### Acceptance Criteria

1. THE EnvManager package SHALL provide a `composer.json` that declares `pradeepdev001/laravel-environment-manager` as the package name, specifies `php: ^8.1` and `laravel/framework: ^10.0|^11.0|^12.0` as dependencies, and defines autoloading under the `Pradeepdev\EnvironmentManager` namespace.
2. THE EnvManager package SHALL provide a `EnvironmentManagerServiceProvider` that registers all package services, commands, routes, views, migrations, and config with the Laravel application.
3. WHEN the package is installed, THE EnvironmentManagerServiceProvider SHALL auto-discover via the `extra.laravel.providers` key in `composer.json`.
4. THE EnvManager package SHALL provide a publishable `config/environment-manager.php` file containing all configurable options including: UI enable/disable, API enable/disable, backup path, backup retention count, masking rules, validation rules, cache commands, allowed users, route prefix, route middleware, and notification channels.
5. THE EnvManager package SHALL provide publishable database migration files for the `env_version_history` and `env_audit_logs` tables.
6. THE EnvManager package SHALL provide publishable Blade view files under the `environment-manager` view namespace.
7. THE EnvManager package SHALL include a `.editorconfig`, `.gitignore`, `phpstan.neon`, `.php-cs-fixer.php` (Laravel Pint config), `CHANGELOG.md`, `LICENSE` (MIT), `CONTRIBUTING.md`, `SECURITY.md`, `CODE_OF_CONDUCT.md`, and `README.md` in the repository root.
8. THE EnvManager package SHALL include GitHub Actions workflows for running Pest tests on PHP 8.1/8.2/8.3 against Laravel 10/11/12, running PHPStan static analysis, running Laravel Pint code style checks, and publishing releases.
9. THE EnvManager package SHALL include a `Dependabot` configuration file, GitHub issue templates, and a pull request template.
10. WHEN `php artisan vendor:publish --tag=environment-manager-config` is run, THE EnvironmentManagerServiceProvider SHALL publish the config file to `config/environment-manager.php` in the host application.
11. WHEN `php artisan vendor:publish --tag=environment-manager-migrations` is run, THE EnvironmentManagerServiceProvider SHALL publish migration files to the host application's `database/migrations` directory.

---

### Requirement 2: Environment File Parsing and Representation

**User Story:** As a developer, I want the package to accurately parse my `.env` file into structured data, so that variables can be displayed and edited without losing comments or formatting.

#### Acceptance Criteria

1. WHEN a `.env` file is read, THE EnvParser SHALL produce a structured list of `EnvVariable` records, each containing: key, raw value, type (string, boolean, integer, null), category, description, sensitivity flag, and line number.
2. WHEN a `.env` file contains comments (lines starting with `#`), THE EnvParser SHALL preserve those comments in the parsed representation and SHALL NOT discard them.
3. WHEN a `.env` file contains blank lines, THE EnvParser SHALL preserve blank lines in the parsed representation to maintain formatting.
4. WHEN a `.env` file contains quoted values (single or double quotes), THE EnvParser SHALL correctly strip the quotes and store the unquoted value, while preserving the original quote style for round-trip writing.
5. WHEN a `.env` file is parsed and then written back without modifications, THE EnvWriter SHALL produce a byte-for-byte identical file (round-trip property).
6. IF the `.env` file does not exist or is not readable, THEN THE EnvParser SHALL throw an `EnvFileNotFoundException` with a descriptive message.
7. IF a `.env` file line contains a malformed entry (missing `=` delimiter), THEN THE EnvParser SHALL skip the line and log a warning rather than throwing a fatal error.
8. THE EnvParser SHALL support `.env` files up to 1 MB in size without performance degradation exceeding 100ms for parsing.
9. FOR ALL valid `.env` files, parsing then printing then parsing SHALL produce an equivalent set of `EnvVariable` records (round-trip property).

---

### Requirement 3: Environment Viewer

**User Story:** As an admin user, I want to view all environment variables in a structured UI, so that I can understand the current configuration of my application at a glance.

#### Acceptance Criteria

1. WHEN the admin UI is accessed, THE UiController SHALL display all `EnvVariable` records grouped by Category in a responsive table.
2. THE UiController SHALL display for each `EnvVariable`: key, masked or revealed value, type, category, and a description if available.
3. WHEN a `Sensitive_Key` is displayed, THE UiController SHALL mask the value as `••••••••` by default regardless of the user's role.
4. WHEN the user performs a search, THE EnvManager SHALL filter displayed variables to those whose key or description contains the search term (case-insensitive).
5. WHEN the user selects a Category filter, THE EnvManager SHALL display only variables belonging to that Category.
6. WHEN the user selects a sort option, THE EnvManager SHALL sort the displayed variables by key (ascending or descending) or by category.
7. THE UiController SHALL support pagination of results when more than a configurable number of variables are displayed.
8. WHERE dark mode is enabled in the browser or OS, THE UiController SHALL render the UI in a dark color scheme.
9. THE UiController SHALL provide a copy-to-clipboard button for each variable's key.
10. WHEN a user with the `view-env` permission accesses the viewer, THE AuthorizationGate SHALL allow access; WHEN a user without the permission accesses it, THE AuthorizationGate SHALL return a 403 response.

---

### Requirement 4: Sensitive Value Protection

**User Story:** As a security-conscious developer, I want sensitive environment values to be automatically masked, so that secrets are never accidentally exposed in the UI or API responses.

#### Acceptance Criteria

1. THE SensitivityDetector SHALL classify a variable as a `Sensitive_Key` if its key matches any of the following patterns: `APP_KEY`, `*_PASSWORD`, `*_SECRET`, `*_SECRET_KEY`, `*_API_KEY`, `*_TOKEN`, `*_PRIVATE_KEY`, `*_ACCESS_KEY`, `JWT_SECRET`, `STRIPE_*`, `AWS_SECRET*`.
2. THE SensitivityDetector SHALL use the configurable `masking_patterns` array in `config/environment-manager.php` to extend or override default sensitivity patterns.
3. WHEN a `Sensitive_Key` value is returned by any API endpoint, THE ApiController SHALL return `"••••••••"` in place of the actual value unless the requesting user has the `reveal-secrets` permission.
4. WHEN a user with the `reveal-secrets` permission clicks the reveal button for a `Sensitive_Key`, THE UiController SHALL display the actual value and THE AuditLogger SHALL record the reveal action with user, key, timestamp, and IP address.
5. WHEN a user without the `reveal-secrets` permission attempts to reveal a `Sensitive_Key`, THE AuthorizationGate SHALL deny the request and return a 403 response.
6. THE EnvManager SHALL never log sensitive values in plaintext to Laravel's application log files.
7. WHEN exporting environment variables, THE ExportFormatter SHALL mask `Sensitive_Key` values unless the requesting user has the `reveal-secrets` permission and has explicitly requested unmasked export.

---

### Requirement 5: Environment Editor

**User Story:** As an admin user, I want to add, update, delete, rename, and bulk-edit environment variables through the UI and API, so that I can manage configuration without direct server access.

#### Acceptance Criteria

1. WHEN a user with the `edit-env` permission submits a new key-value pair, THE EnvManager SHALL add the variable to the `.env` file using an `Atomic_Write` with a `File_Lock`, create a `VersionHistory` record, and run configured cache commands.
2. WHEN a user with the `edit-env` permission submits an updated value for an existing key, THE EnvManager SHALL update the variable in the `.env` file using an `Atomic_Write` with a `File_Lock`, create a `VersionHistory` record, and run configured cache commands.
3. WHEN a user with the `delete-env` permission submits a delete request for an existing key, THE EnvManager SHALL remove the variable from the `.env` file, create a `VersionHistory` record, and run configured cache commands.
4. WHEN a user with the `edit-env` permission renames a key, THE EnvManager SHALL remove the old key, add the new key with the same value, create a `VersionHistory` record noting the rename, and run configured cache commands.
5. WHEN a user with the `edit-env` permission submits a bulk edit payload containing multiple key-value pairs, THE EnvManager SHALL apply all changes as a single `Atomic_Write` operation and create a single bulk `VersionHistory` record.
6. WHEN saving any change, THE BackupManager SHALL create a `Backup_File` of the current `.env` before applying the change.
7. WHEN the `.env` file contains comments adjacent to a variable, THE EnvWriter SHALL preserve those comments after the variable is updated.
8. WHEN a user without the `edit-env` permission attempts to create, update, or rename a variable, THE AuthorizationGate SHALL deny the request and return a 403 response.
9. WHEN a user without the `delete-env` permission attempts to delete a variable, THE AuthorizationGate SHALL deny the request and return a 403 response.
10. IF the `.env` file is locked by another process during a write, THEN THE EnvWriter SHALL retry the lock acquisition up to 3 times with 100ms intervals before returning a `FileLockException`.

---

### Requirement 6: Variable Validation

**User Story:** As a developer, I want environment variable values to be validated before they are saved, so that invalid configurations don't reach production and break the application.

#### Acceptance Criteria

1. WHEN a variable with key `APP_URL` is saved, THE EnvManager SHALL validate that its value is a well-formed URL; IF the value is not a valid URL, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
2. WHEN a variable with key `MAIL_PORT` is saved, THE EnvManager SHALL validate that its value is an integer between 1 and 65535; IF the value fails this check, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
3. WHEN a variable with key `DB_CONNECTION` is saved, THE EnvManager SHALL validate that its value is one of `mysql`, `pgsql`, `sqlite`, `sqlsrv`, `mariadb`; IF the value is not in this list, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
4. WHEN a variable with key `CACHE_DRIVER` is saved, THE EnvManager SHALL validate that its value is one of `array`, `file`, `database`, `redis`, `memcached`, `dynamodb`, `octane`; IF the value fails validation, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
5. WHEN a variable with key `QUEUE_CONNECTION` is saved, THE EnvManager SHALL validate that its value is one of `sync`, `database`, `beanstalkd`, `sqs`, `redis`, `null`; IF the value fails validation, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
6. WHEN a variable with key `SESSION_DRIVER` is saved, THE EnvManager SHALL validate that its value is one of `file`, `cookie`, `database`, `apc`, `memcached`, `redis`, `dynamodb`, `array`; IF the value fails validation, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
7. WHEN a variable with key `MAIL_MAILER` is saved, THE EnvManager SHALL validate that its value is one of `smtp`, `sendmail`, `mailgun`, `ses`, `sparkpost`, `log`, `array`, `failover`; IF the value fails validation, THEN THE EnvManager SHALL reject the change and return a descriptive validation error.
8. THE EnvManager SHALL use the configurable `validation_rules` array in `config/environment-manager.php` to define custom validation rules for any key, supporting: `url`, `email`, `integer`, `boolean`, `enum`, `required`, `nullable`, `min`, `max`, `regex`.
9. WHEN a bulk import is submitted, THE EnvManager SHALL validate all variables in the import payload before applying any changes; IF any variable fails validation, THEN THE EnvManager SHALL reject the entire import and return a list of all validation errors.
10. WHEN a required variable (as defined in `validation_rules`) has an empty value, THE EnvManager SHALL return a validation error indicating the variable is required.

---

### Requirement 7: Category Auto-Grouping

**User Story:** As an admin user, I want environment variables automatically grouped into logical categories, so that I can navigate large `.env` files efficiently.

#### Acceptance Criteria

1. THE EnvManager SHALL automatically assign each `EnvVariable` to a Category based on its key prefix according to the following rules: `APP_*` → Application; `DB_*` → Database; `MAIL_*` or `MAILGUN_*` or `SES_*` → Mail; `CACHE_*` or `MEMCACHED_*` → Cache; `QUEUE_*` → Queue; `BROADCAST_*` or `PUSHER_*` or `ABLY_*` → Broadcast; `FILESYSTEM_*` or `AWS_*` and filesystem-related → Filesystem; `REDIS_*` → Redis; `AWS_*` → AWS; `STRIPE_*` → Stripe; all others → Custom.
2. WHEN a key matches multiple category patterns, THE EnvManager SHALL assign the more specific pattern (e.g., `AWS_BUCKET` goes to Filesystem if `FILESYSTEM_DISK=s3`, otherwise AWS).
3. THE EnvManager SHALL use the configurable `categories` map in `config/environment-manager.php` to override or extend the default category assignment rules.
4. WHEN the UI displays variables, THE UiController SHALL render a collapsible section for each Category that contains at least one `EnvVariable`.
5. THE EnvManager SHALL display a count of variables in each Category header.

---

### Requirement 8: Backup Management

**User Story:** As a developer, I want automatic and manual backups of my `.env` file, so that I can recover quickly from accidental or incorrect changes.

#### Acceptance Criteria

1. WHEN any write operation (add, update, delete, rename, bulk edit) is performed, THE BackupManager SHALL automatically create a `Backup_File` of the `.env` file before the change is applied.
2. WHEN a user with the `backup-env` permission clicks the manual backup button, THE BackupManager SHALL create a `Backup_File` immediately.
3. WHEN a scheduled backup is configured in `config/environment-manager.php`, THE BackupManager SHALL create a `Backup_File` on the configured schedule using Laravel's task scheduler.
4. WHEN a `Backup_File` is created, THE BackupManager SHALL name it with the pattern `env_backup_{timestamp}_{hash}.env` and store it in the configured `backup_path`.
5. WHEN the number of `Backup_File` records exceeds the configured `backup_retention` count, THE BackupManager SHALL delete the oldest `Backup_File` records to maintain the retention limit.
6. WHEN a user with the `restore-env` permission selects a `Backup_File` and confirms restoration, THE BackupManager SHALL replace the current `.env` file with the `Backup_File` contents using an `Atomic_Write`, create a `VersionHistory` record noting the restoration, and run configured cache commands.
7. WHEN a user with the `backup-env` permission clicks download for a `Backup_File`, THE BackupManager SHALL stream the file as an encrypted download response.
8. WHEN a user with the `backup-env` permission deletes a `Backup_File`, THE BackupManager SHALL remove the file from storage and its metadata from the database.
9. IF the backup storage path is not writable, THEN THE BackupManager SHALL throw a `BackupStorageException` and notify the configured administrators.
10. WHEN the `backup_encryption` option is enabled in config, THE BackupManager SHALL encrypt `Backup_File` contents using AES-256 before writing to disk.

---

### Requirement 9: Version History and Rollback

**User Story:** As a developer, I want every environment change tracked with full context, so that I can audit changes and roll back to any previous state.

#### Acceptance Criteria

1. WHEN any write operation is performed, THE VersionHistory component SHALL create a record containing: user ID, user name, action type (create/update/delete/rename/restore/bulk), variable key, previous value (masked if sensitive), new value (masked if sensitive), reason (optional user-provided text), IP address, and UTC timestamp.
2. THE UiController SHALL display the version history as a paginated, sortable, filterable list showing all `VersionHistory` records.
3. WHEN a user filters the history by key, THE EnvManager SHALL display only records matching that key.
4. WHEN a user filters the history by date range, THE EnvManager SHALL display only records within that date range.
5. WHEN a user with the `restore-env` permission selects a `VersionHistory` record and confirms rollback, THE EnvManager SHALL restore the variable to the value recorded in that history entry using an `Atomic_Write` and create a new `VersionHistory` record noting the rollback.
6. WHEN a rollback is performed on a variable that was deleted in history, THE EnvManager SHALL recreate the variable with the historical value.
7. THE EnvManager SHALL never store actual sensitive values in `VersionHistory` records; WHEN a variable is a `Sensitive_Key`, THE VersionHistory component SHALL store `"[REDACTED]"` as the value.
8. THE EnvManager SHALL retain `VersionHistory` records for the number of days configured in `version_history_retention_days`; WHEN records exceed this retention period, THE EnvManager SHALL prune them via a scheduled Artisan command.

---

### Requirement 10: Diff Viewer

**User Story:** As a developer, I want to see a visual diff between two versions of the `.env` file, so that I can understand exactly what changed between any two points in time.

#### Acceptance Criteria

1. WHEN a user selects two `VersionHistory` snapshots or two `Backup_File` records, THE DiffEngine SHALL compute and display a line-by-line diff highlighting: added lines (green), removed lines (red), and modified lines (yellow).
2. THE DiffEngine SHALL display the key, old value, and new value for each modified variable, with `Sensitive_Key` values always masked.
3. WHEN the diff contains no changes, THE DiffEngine SHALL display a message indicating the two versions are identical.
4. THE UiController SHALL render the diff in a side-by-side or unified view, togglable by the user.
5. WHEN a user with the `edit-env` permission views a diff, THE UiController SHALL provide a "Restore to this version" button for each compared snapshot.

---

### Requirement 11: Import and Export

**User Story:** As a developer, I want to import and export environment variables in multiple formats, so that I can migrate configuration between environments and systems.

#### Acceptance Criteria

1. WHEN a user with the `edit-env` permission uploads a `.env` file, THE ImportProcessor SHALL parse it, validate all variables against configured rules, and display a preview diff before applying changes.
2. WHEN a user with the `edit-env` permission uploads a JSON file, THE ImportProcessor SHALL parse the JSON object as key-value pairs, validate all variables, and display a preview diff before applying changes.
3. WHEN the user confirms a validated import, THE ImportProcessor SHALL apply the changes using a single `Atomic_Write`, create a bulk `VersionHistory` record, and run configured cache commands.
4. IF any variable in an import payload fails validation, THEN THE ImportProcessor SHALL reject the entire import and return a detailed list of all validation errors without applying any changes.
5. WHEN a user with the `view-env` permission requests an export, THE ExportFormatter SHALL export all variables as a `.env`-formatted file, with `Sensitive_Key` values masked unless the user has `reveal-secrets` permission.
6. WHEN a user with the `view-env` permission requests a JSON export, THE ExportFormatter SHALL export all variables as a JSON object, with `Sensitive_Key` values masked unless the user has `reveal-secrets` permission.
7. WHEN a user with the `view-env` permission requests a YAML export, THE ExportFormatter SHALL export all variables as a YAML document, with `Sensitive_Key` values masked unless the user has `reveal-secrets` permission.
8. FOR ALL valid `.env` files, importing then exporting as `.env` SHALL produce a file that, when parsed, yields an equivalent set of `EnvVariable` records (round-trip property).

---

### Requirement 12: Environment Comparison

**User Story:** As a DevOps engineer, I want to compare environment variables across Local, Development, Staging, and Production environments, so that I can identify configuration drift and missing variables.

#### Acceptance Criteria

1. THE EnvManager SHALL support configuring multiple environment snapshots (Local, Development, Staging, Production) with their respective `.env` file paths or uploaded contents in `config/environment-manager.php`.
2. WHEN a user selects two or more environments to compare, THE DiffEngine SHALL display a matrix view showing all keys across the selected environments, with values shown per environment and `Sensitive_Key` values masked.
3. WHEN a variable exists in one environment but not another, THE DiffEngine SHALL highlight the missing variable as absent for that environment.
4. WHEN a variable has different values across environments, THE DiffEngine SHALL highlight the differing values for visual comparison, with `Sensitive_Key` values always masked.
5. THE EnvManager SHALL allow exporting the comparison result as a CSV or JSON report.

---

### Requirement 13: Cache Management

**User Story:** As a developer, I want the package to automatically clear and rebuild Laravel caches after every environment change, so that configuration changes take effect immediately without manual intervention.

#### Acceptance Criteria

1. WHEN a write operation is successfully completed, THE CacheManager SHALL execute the cache commands configured in the `cache_commands` array in `config/environment-manager.php`.
2. THE default `cache_commands` array SHALL include: `config:clear`, `config:cache`, `route:cache`, `view:cache`, `event:cache`.
3. WHEN `cache_after_save` is set to `false` in config, THE CacheManager SHALL skip all cache commands after a write operation.
4. WHEN a configured cache command fails, THE CacheManager SHALL log the failure and continue executing remaining commands rather than halting the entire operation.
5. THE UiController SHALL display the result of each cache command run after a save operation, showing success or failure per command.
6. WHEN a user manually triggers cache clearing from the UI, THE CacheManager SHALL execute the configured cache commands and return the results.

---

### Requirement 14: Artisan Commands

**User Story:** As a developer, I want to manage environment variables from the command line, so that I can automate configuration management in CI/CD pipelines and deployment scripts.

#### Acceptance Criteria

1. THE EnvManager package SHALL provide an `env-manager:list` command that displays all `EnvVariable` records in a formatted table with key, masked value, type, and category; it SHALL support `--category`, `--search`, and `--format=table|json` options.
2. THE EnvManager package SHALL provide an `env-manager:get {key}` command that displays the value of a single variable; it SHALL mask `Sensitive_Key` values by default and accept a `--reveal` flag (which requires authentication confirmation or `APP_ENV=local`).
3. THE EnvManager package SHALL provide an `env-manager:set {key} {value}` command that adds or updates a variable, validates the value against configured rules, creates a `VersionHistory` record, and runs configured cache commands; it SHALL accept an optional `--reason="..."` argument.
4. THE EnvManager package SHALL provide an `env-manager:delete {key}` command that removes a variable, creates a `VersionHistory` record, and runs configured cache commands; it SHALL prompt for confirmation before deletion.
5. THE EnvManager package SHALL provide an `env-manager:backup` command that creates a manual `Backup_File` and outputs the backup file path.
6. THE EnvManager package SHALL provide an `env-manager:restore {backup_name}` command that restores a `Backup_File` after prompting for confirmation, creates a `VersionHistory` record, and runs configured cache commands.
7. THE EnvManager package SHALL provide an `env-manager:compare {env1} {env2}` command that displays a diff between two environment snapshots in the terminal.
8. THE EnvManager package SHALL provide an `env-manager:validate` command that validates the current `.env` file against all configured validation rules and outputs a pass/fail result per variable.
9. IF a key provided to `env-manager:get` or `env-manager:delete` does not exist in the `.env` file, THEN THE command SHALL output a descriptive error and exit with a non-zero exit code.
10. WHEN any Artisan command modifies the `.env` file, THE AuditLogger SHALL record the action with source `cli`, the authenticated user (if available), and timestamp.

---

### Requirement 15: Authorization and Role-Based Access Control

**User Story:** As a security administrator, I want fine-grained role-based access control for all package operations, so that team members can only perform actions appropriate to their role.

#### Acceptance Criteria

1. THE AuthorizationGate SHALL define the following permissions: `view-env`, `edit-env`, `delete-env`, `backup-env`, `restore-env`, `reveal-secrets`.
2. THE EnvManager package SHALL define a `Super_Admin` role that is granted all six permissions by default.
3. THE EnvManager package SHALL define an `Admin` role that is granted `view-env`, `edit-env`, `delete-env`, `backup-env`, and `restore-env` by default; the `reveal-secrets` permission for `Admin` SHALL be configurable in `config/environment-manager.php`.
4. THE EnvManager package SHALL define a `Read_Only` role that is granted only `view-env` by default.
5. THE `allowed_users` configuration key in `config/environment-manager.php` SHALL accept a callable or array of user IDs that defines which users can access the package UI at all.
6. WHEN the `authorization_callback` in config is defined, THE AuthorizationGate SHALL delegate the authorization decision to that callback, enabling integration with existing permission systems (e.g., Spatie Permission, Bouncer).
7. WHEN a user accesses any package route, THE AuthorizationGate SHALL verify both the `allowed_users` list and the specific permission for the action before granting access.
8. IF the requesting user is not in the `allowed_users` list, THEN THE AuthorizationGate SHALL return a 403 response regardless of their role.
9. WHEN `APP_ENV=local` and the `bypass_auth_in_local` option is `true` in config, THE AuthorizationGate SHALL grant all permissions to all users (intended for development convenience only).

---

### Requirement 16: Audit Logging

**User Story:** As a compliance officer, I want a complete audit log of all environment management actions, so that I can trace who changed what, when, and from where.

#### Acceptance Criteria

1. THE AuditLogger SHALL record a log entry for every action performed through the UI, API, or Artisan commands including: action type, variable key, old value (masked if sensitive), new value (masked if sensitive), user ID, user name, source (ui/api/cli), IP address, user agent (browser), and UTC timestamp.
2. THE UiController SHALL display the audit log as a paginated, filterable, sortable table accessible to users with the `view-env` permission.
3. WHEN a user filters the audit log by action type, user, key, or date range, THE EnvManager SHALL return only matching records.
4. THE AuditLogger SHALL store audit records in the `env_audit_logs` database table.
5. THE EnvManager SHALL retain audit log records for the number of days configured in `audit_log_retention_days`; WHEN records exceed this retention, THE EnvManager SHALL prune them via a scheduled Artisan command.
6. THE AuditLogger SHALL never store actual sensitive values; WHEN an action involves a `Sensitive_Key`, THE AuditLogger SHALL store `"[REDACTED]"` as the value.
7. WHEN a `Sensitive_Key` reveal action is performed, THE AuditLogger SHALL record the reveal with the user ID, key name, timestamp, and IP address (but not the revealed value).

---

### Requirement 17: Notifications

**User Story:** As a team lead, I want to receive notifications when critical environment variables are changed, so that I can respond quickly to potentially dangerous configuration changes.

#### Acceptance Criteria

1. WHEN a write operation is performed on any variable, THE NotificationDispatcher SHALL send a notification to all configured channels if `notifications.on_env_update` is `true` in config.
2. WHEN a `Sensitive_Key` is modified, THE NotificationDispatcher SHALL always send a notification to all configured channels regardless of the general `on_env_update` setting.
3. WHEN the variable `APP_KEY` is changed, THE NotificationDispatcher SHALL send a high-priority notification to all configured channels.
4. WHEN any `DB_*` credential variable is changed, THE NotificationDispatcher SHALL send a high-priority notification to all configured channels.
5. THE NotificationDispatcher SHALL support the following notification channels, each configurable in `config/environment-manager.php`: Mail, Slack (via webhook), Microsoft Teams (via webhook), and custom Webhook.
6. WHEN the `Mail` channel is configured, THE NotificationDispatcher SHALL send an email to all addresses listed in `notifications.mail.recipients`.
7. WHEN a Slack or Teams webhook URL is configured, THE NotificationDispatcher SHALL send a formatted message to that webhook.
8. WHEN a custom webhook URL is configured, THE NotificationDispatcher SHALL POST a JSON payload containing action, key, user, timestamp, and environment to that URL.
9. IF a notification dispatch fails, THE NotificationDispatcher SHALL log the failure and SHALL NOT block or roll back the environment change operation.

---

### Requirement 18: Encryption

**User Story:** As a security engineer, I want backup files, export files, and cached sensitive data to be encrypted, so that secrets are protected at rest.

#### Acceptance Criteria

1. WHEN `backup_encryption` is enabled in config, THE BackupManager SHALL encrypt every `Backup_File` using AES-256-CBC with a key derived from `APP_KEY` before writing to disk.
2. WHEN a user downloads a `Backup_File` with encryption enabled, THE BackupManager SHALL decrypt the file in memory and stream the plaintext to the user over an authenticated HTTPS response.
3. WHEN `export_encryption` is enabled in config and a user requests an export, THE ExportFormatter SHALL encrypt the export file before sending it as a download response.
4. THE EnvManager SHALL never cache sensitive values in Laravel's cache store in plaintext; WHEN caching is used internally, THE EnvManager SHALL encrypt any `Sensitive_Key` values before storing them in the cache.
5. THE EnvManager SHALL not expose `APP_KEY` or derived encryption keys in any UI, API response, log, or export output.

---

### Requirement 19: Web-Based Admin Interface

**User Story:** As an admin user, I want a responsive, accessible web interface for managing environment variables, so that I can use the package without writing any code.

#### Acceptance Criteria

1. THE UiController SHALL serve the admin UI at the configurable `route_prefix` (default: `/env-manager`) with the configured `route_middleware` applied.
2. THE admin UI SHALL be built with Blade templates and shall be compatible with Livewire when the host application has Livewire installed.
3. THE admin UI SHALL be fully responsive and accessible on desktop, tablet, and mobile screen sizes.
4. THE admin UI SHALL include: a variable list view, an add/edit form, a delete confirmation dialog, a bulk edit interface, an import/export panel, a backup management panel, a version history view, a diff viewer, an audit log view, and a settings overview.
5. THE admin UI SHALL include a search bar, category filter dropdown, sort controls, and pagination controls on the variable list view.
6. THE admin UI SHALL display validation error messages inline next to the relevant form field when a save operation fails validation.
7. THE admin UI SHALL display a success notification and the results of any cache commands run after a successful save operation.
8. THE admin UI SHALL provide a "Reveal Secret" button for `Sensitive_Key` variables, visible only to users with the `reveal-secrets` permission.
9. THE admin UI SHALL include a copy-to-clipboard button for variable keys and (for non-sensitive variables) values.
10. WHERE dark mode is preferred by the OS or browser, THE admin UI SHALL render in a dark color scheme using CSS media queries or a user-toggleable theme switcher.
11. THE admin UI SHALL protect all state-modifying actions with CSRF tokens.
12. THE admin UI SHALL be registerable as a standalone Blade-only interface with no Livewire or Inertia dependency; optional enhancement with Livewire or Inertia/Vue SHALL be documented.

---

### Requirement 20: REST API

**User Story:** As a DevOps engineer, I want a REST API for environment management, so that I can integrate environment configuration into automated CI/CD pipelines and external tooling.

#### Acceptance Criteria

1. WHEN the `enable_api` option is `true` in config, THE ApiController SHALL register the following routes under the configured `api_prefix` (default: `/api/env-manager`) with the configured `api_middleware`:
   - `GET /env` – list all variables
   - `POST /env` – create a new variable
   - `PUT /env/{key}` – update an existing variable
   - `DELETE /env/{key}` – delete a variable
   - `GET /env/history` – list version history records
   - `GET /env/backups` – list available backups
2. ALL API responses SHALL use JSON and follow the structure `{ "success": bool, "data": ..., "message": "..." }`.
3. WHEN the `GET /env` endpoint is called, THE ApiController SHALL return all `EnvVariable` records with `Sensitive_Key` values masked unless the requesting user has the `reveal-secrets` permission.
4. WHEN the `POST /env` or `PUT /env/{key}` endpoint is called with an invalid value, THE ApiController SHALL return HTTP 422 with a JSON body listing all validation errors.
5. WHEN the `DELETE /env/{key}` endpoint is called for a non-existent key, THE ApiController SHALL return HTTP 404.
6. WHEN an API request is made without proper authorization, THE AuthorizationGate SHALL cause the ApiController to return HTTP 403.
7. THE ApiController SHALL apply rate limiting to all endpoints using Laravel's built-in rate limiter, configurable via `api_rate_limit` in config.
8. THE REST API SHALL be protected by the configured `api_middleware`, which defaults to `['auth:sanctum', 'throttle:api']`.
9. THE ApiController SHALL support filtering on `GET /env` via query parameters: `?category=`, `?search=`, `?page=`, `?per_page=`.

---

### Requirement 21: Performance and Atomic File Operations

**User Story:** As a developer running a high-traffic application, I want environment file reads and writes to be fast, safe, and free from corruption, so that environment management never impacts application stability.

#### Acceptance Criteria

1. WHEN a write operation is performed, THE EnvWriter SHALL use an `Atomic_Write` strategy: write to a temporary file in the same directory, then use `rename()` to atomically replace the target `.env` file.
2. WHEN a write operation is in progress, THE EnvWriter SHALL acquire a `File_Lock` using `flock()` with exclusive lock (`LOCK_EX`) before writing and release it after the rename completes.
3. WHEN multiple write requests arrive concurrently, THE EnvWriter SHALL serialize them using `File_Lock` to prevent concurrent write corruption.
4. THE EnvParser SHALL read the `.env` file in a single buffered read and SHALL complete parsing of a 1,000-variable `.env` file within 100ms on standard hardware.
5. THE EnvManager SHALL cache the parsed `EnvVariable` list in Laravel's cache store for the duration configured in `cache_ttl` (default: 0 seconds, i.e., no caching) and SHALL invalidate the cache on any write operation.
6. WHEN a `.env` file exceeds 1 MB in size, THE EnvParser SHALL log a warning but SHALL still complete parsing without error.
7. THE EnvWriter SHALL never leave a partial or empty `.env` file on disk; IF the write fails for any reason, THEN the original `.env` file SHALL remain unchanged.

---

### Requirement 22: Security Hardening

**User Story:** As a security engineer, I want the package to follow security best practices throughout, so that it does not introduce vulnerabilities into the applications that use it.

#### Acceptance Criteria

1. ALL routes provided by the package SHALL have CSRF protection applied via Laravel's `VerifyCsrfToken` middleware or equivalent for API routes using Sanctum.
2. THE EnvManager SHALL validate and sanitize all user inputs (key names, values, import payloads) before processing to prevent injection attacks.
3. WHEN a key name is submitted via the API or UI, THE EnvManager SHALL validate that it matches the pattern `[A-Z_][A-Z0-9_]*` (uppercase letters, digits, underscores) and reject names that do not conform.
4. THE EnvManager SHALL never expose the contents of `APP_KEY` or any `Sensitive_Key` in HTTP response headers, error messages, stack traces, or log entries.
5. THE EnvManager routes SHALL be protected by authentication middleware by default; the `route_middleware` config key SHALL default to `['web', 'auth']`.
6. WHEN `APP_ENV` is `production`, THE EnvManager SHALL require the user to be authenticated and authorized regardless of any other configuration; THE `bypass_auth_in_local` option SHALL have no effect in production.
7. THE EnvManager SHALL not execute arbitrary shell commands beyond the configured `cache_commands` list; all Artisan command execution SHALL use Laravel's built-in `Artisan::call()` API with whitelisted command names.
8. WHEN generating download responses for backup or export files, THE EnvManager SHALL set appropriate HTTP headers (`Content-Disposition: attachment`, `X-Content-Type-Options: nosniff`) to prevent content sniffing.

---

### Requirement 23: Testing

**User Story:** As a maintainer, I want a comprehensive Pest test suite, so that every feature is verified to work correctly, regressions are caught, and the package can be released with confidence.

#### Acceptance Criteria

1. THE EnvManager package SHALL include PestTest files covering the following areas: `EnvParser` (parsing, round-trip, malformed input, large files), `EnvWriter` (atomic write, file lock, comment preservation, round-trip), `SensitivityDetector` (pattern matching, custom patterns), validation rules, `BackupManager` (create, restore, delete, encryption, retention), `VersionHistory` (record creation, rollback, pruning), `DiffEngine` (diff correctness, identical files, added/removed/modified), `ImportProcessor` (valid import, invalid import rejection, round-trip), `ExportFormatter` (env/JSON/YAML output, round-trip), `ApiController` (all endpoints, auth, validation, masking), `UiController` (all views, auth, CSRF), Artisan commands (all 8 commands), and authorization (all permissions, all roles).
2. THE `EnvParser` + `EnvWriter` round-trip PestTest SHALL use property-based testing to verify that parsing then writing any valid `.env` file content produces an equivalent file (Requirement 2, Criterion 9).
3. THE `ImportProcessor` + `ExportFormatter` round-trip PestTest SHALL use property-based testing to verify that import then export produces equivalent records (Requirement 11, Criterion 8).
4. THE PestTest suite SHALL achieve at least 90% code coverage across all package source files.
5. THE package SHALL include a `phpunit.xml` (or `phpunit.xml.dist`) configured to run Pest tests with code coverage reporting.
6. THE package SHALL include an Orchestra Testbench-based test environment that bootstraps a minimal Laravel application for testing without requiring a full Laravel installation.
7. ALL security-related behaviors (CSRF, auth bypass prevention, sensitive value masking, injection prevention) SHALL have dedicated PestTest cases.
8. ALL edge cases (empty `.env` file, `.env` file with only comments, very long values, special characters in values, unicode in values) SHALL have dedicated PestTest cases.

---

### Requirement 24: Documentation

**User Story:** As a developer adopting this package, I want comprehensive documentation, so that I can install, configure, and use every feature without reverse-engineering the code.

#### Acceptance Criteria

1. THE package `README.md` SHALL include: a project description, badges (CI, coverage, Packagist version, license), requirements (PHP/Laravel versions), installation instructions, quick-start example, link to full documentation, and a license section.
2. THE package SHALL provide a `docs/` directory with the following files: `installation.md`, `configuration.md`, `quick-start.md`, `api-reference.md`, `ui-guide.md`, `artisan-commands.md`, `security.md`, `backup-restore.md`, `version-history.md`, `import-export.md`, `environment-comparison.md`, `authorization.md`, `notifications.md`, `testing.md`, `upgrade.md`, `faq.md`, and `contributing.md`.
3. THE `configuration.md` SHALL document every key in `config/environment-manager.php` with its type, default value, and description.
4. THE `api-reference.md` SHALL document every REST API endpoint with method, URL, request parameters, example request, and example response.
5. THE `artisan-commands.md` SHALL document every Artisan command with its signature, all options and arguments, and example usage.
6. THE `security.md` SHALL document the security model, sensitive key detection, encryption options, authorization model, and recommendations for production deployments.
7. THE `CHANGELOG.md` SHALL follow the Keep a Changelog format with entries for every released version organized under `Added`, `Changed`, `Fixed`, `Removed`, `Security`.
8. THE `CONTRIBUTING.md` SHALL include instructions for setting up a development environment, running tests, submitting issues, and the pull request process.
9. THE `SECURITY.md` SHALL describe the responsible disclosure process and contact information for reporting security vulnerabilities.

---

### Requirement 25: Open-Source Governance and Repository Setup

**User Story:** As an open-source project maintainer, I want all OSS governance files and automation in place, so that the project follows community standards and contributors can participate effectively.

#### Acceptance Criteria

1. THE repository SHALL contain a `LICENSE` file with the MIT license text and copyright assigned to the package author.
2. THE repository SHALL contain a `CODE_OF_CONDUCT.md` based on the Contributor Covenant v2.1.
3. THE repository SHALL contain a `.github/ISSUE_TEMPLATE/` directory with templates for: bug report, feature request, and question.
4. THE repository SHALL contain a `.github/PULL_REQUEST_TEMPLATE.md` with sections for: description, type of change, testing done, checklist.
5. THE repository SHALL contain a `.github/workflows/tests.yml` GitHub Actions workflow that: runs Pest tests on PHP 8.1, 8.2, and 8.3 against Laravel 10, 11, and 12 (matrix), uploads coverage reports, and fails the build on test failure.
6. THE repository SHALL contain a `.github/workflows/static-analysis.yml` GitHub Actions workflow that runs PHPStan at level 8 and fails on any errors.
7. THE repository SHALL contain a `.github/workflows/code-style.yml` GitHub Actions workflow that runs Laravel Pint and fails if the code style is not compliant.
8. THE repository SHALL contain a `.github/dependabot.yml` configuration file that checks for GitHub Actions and Composer dependency updates weekly.
9. THE repository SHALL contain a `.editorconfig` file specifying: `indent_style = space`, `indent_size = 4`, `end_of_line = lf`, `charset = utf-8`, `trim_trailing_whitespace = true`, `insert_final_newline = true`.
10. THE repository SHALL contain a `phpstan.neon` file configured with `level: 8`, all package source directories in the `paths` array, and appropriate `ignoreErrors` for Laravel-specific patterns.
