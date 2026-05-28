# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `after()` method for cross-field validation hooks
- `Rule::sometimes()` for optional field validation (skip when null)
- `Rule::bail()` for per-attribute short-circuit validation (stop at first
  failure)
- `sometimes()` instance method for conditionally applying rules based on input
  data
- Nested array validation via dot notation (`'address.city'`)
- Wildcard array validation (`'items.*.name'`) — expands to match actual array
  indices
- `messages()` override in custom validator classes for per-attribute error
  messages
- `Countable` interface on `Errors` and `ValidatedInput` — supports `count()`
- `Errors::reset()`, `Errors::toArray()`, `ValidatedInput::reset()`,
  `ValidatedInput::toArray()`
- `ValidatedInput::has()` — check if an attribute was validated
- All violations per attribute are now collected (not just the first)
- Cached Symfony validator instance for performance
- GitHub Actions CI workflow

### Changed

- `Errors` and `ValidatedInput` now implement `IteratorAggregate` instead of
  `Iterator`
- `Errors::$errors` visibility changed from `public` to `protected`
- `macro()` now requires `Closure` parameter (was `callable`)
- `requiredIf()` uses strict null/empty-string check instead of `empty()`
- `setData()` resets validation state (errors and validated data cleared)
- `CustomConstraintValidator` safely handles non-scalar values

### Fixed

- Iterator bug: empty collections no longer yield a phantom iteration
- `ValidationRule::$passed` now resets on each `performValidation()` call
- `validate()` no longer accumulates errors/data on repeated calls
- `addRule()` for new attributes no longer triggers undefined key warning
- Replaced deprecated `get_class()` with `static::class`
- Removed backslash-prefixed global function calls

### Removed

- `ValidatedInput::$hasNext` and `Errors::$hasNext` properties (replaced by
  `IteratorAggregate`)
