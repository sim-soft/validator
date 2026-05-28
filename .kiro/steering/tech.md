# Tech Stack

## Language & Runtime

- PHP >= 8.4
- Uses modern PHP features: named arguments, union types, promoted constructor
  properties, `static` return types, enums-style constants

## Dependencies

- **symfony/validator ^8** — Core validation engine (constraints, constraint
  validators, validation builder)

## Dev Dependencies

- **phpunit/phpunit ^11** — Testing framework

## Build & Autoloading

- Composer (PSR-4 autoloading)
- Namespace `Simsoft\` maps to `src/Validator/`
- Namespace `Test\` maps to `tests/Validators/` (dev only)

## Common Commands

```bash
# Install dependencies
composer install

# Run tests
composer test
# or directly:
phpunit --display-deprecations tests
```

## Code Style

- 4-space indentation for PHP files
- LF line endings, UTF-8 charset
- `declare(strict_types=1)` in test files
- PHPDoc blocks on all public/protected methods
- Named arguments preferred for Symfony constraint constructors
