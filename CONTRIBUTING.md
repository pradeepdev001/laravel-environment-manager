# Contributing

Thank you for considering contributing to `laravel-environment-manager`!

## Development Setup

```bash
git clone https://github.com/pradeepdev001/laravel-environment-manager.git
cd laravel-environment-manager
composer install
```

## Running Tests

```bash
composer test
```

With coverage:

```bash
composer test-coverage
```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code style:

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## Submitting Changes

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Write tests for your changes
4. Ensure all tests pass: `composer test`
5. Ensure code style is clean: `composer format`
6. Ensure static analysis passes: `composer analyse`
7. Commit with a descriptive message
8. Push and open a Pull Request

## Pull Request Guidelines

- Reference any related issues
- Describe what changed and why
- Ensure the PR passes all CI checks
- Add entries to `CHANGELOG.md` under `[Unreleased]`
- Keep PRs focused — one feature or fix per PR

## Reporting Issues

Please use the [GitHub issue tracker](https://github.com/pradeepdev001/laravel-environment-manager/issues).
Use the appropriate issue template (bug report, feature request, or question).
