# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Rule::bail()` for per-attribute short-circuit validation (stop at first
  failure)
- `Rule::sometimes()` for optional field validation (skip when null)
- `after()` method for cross-field validation hooks
- `sometimes()` instance method for conditionally applying rules based on input
  data
- Nested array validation via dot notation (`'address.city'`)
- Wildcard array validation (`'items.*.name'`) — expands to match actual array
  indices
- `messages()` override in custom validator classes for per-attribute error
  messages
- `Countable` interface on `Errors` and `ValidatedInput` — supports `count()`
- `ValidatedInput::has()` — check if an attribute was validated
- `ValidatedInput::toArray()` and `Errors::toArray()` — alias for `all()`
- `Errors::reset()` and `ValidatedInput::reset()` methods
- All violations per attribute are now collected (not just the first)
- Cached Symfony validator instance for performance
- GitHub Actions CI workflow
- Docsify documentation site with a dark / light theme
- Constraints Reference page with copy-paste examples for 30+ constraints
- "Why Object-Based Rules?" explainer page

### Changed

- `Errors` and `ValidatedInput` now implement `IteratorAggregate` instead of
  `Iterator`
- `Errors::$errors` visibility changed from `public` to `protected`
- `macro()` now requires `Closure` parameter (was `callable`)
- `requiredIf()` uses strict null/empty-string check instead of `empty()`
- `setData()` resets validation state (errors and validated data cleared)
- `CustomConstraintValidator` safely handles non-scalar values
- Documentation uses `Rule::bail()` instead of `Sequentially`

### Fixed

- Iterator bug: empty collections no longer yield a phantom iteration
- `ValidationRule::$passed` now resets on each `performValidation()` call
- `validate()` no longer accumulates errors/data on repeated calls
- `addRule()` for new attributes no longer triggers undefined key warning
- Replaced deprecated `get_class()` with `static::class`
- Removed backslash-prefixed global function calls

### Removed

- `Validator::extend()` (dead code with no consumer)
- `ValidatedInput::$hasNext` and `Errors::$hasNext` properties (replaced by
  `IteratorAggregate`)
