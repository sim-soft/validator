# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.0.0] - 2026-09-07

This is a major release. It contains breaking changes to `Errors`,
`ValidatedInput`, `macro()`, and the supported `symfony/validator` range, and
it fixes three security defects in validation state handling. Review the
Security and Changed sections before upgrading.

### Added

- `Rule::bail()` for per-attribute short-circuit validation (stop at first
  failure)
- `Rule::sometimes()` for optional field validation (skip when null)
- `Rule::each()` for validating every item in an array value
- `Rule::anyOf()` for passing if at least one constraint matches
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
- Documented that `Validator` instances are request-scoped and must not be
  shared — see "One Validator per Validation" in the usage guide. An instance
  holds the input, validated data and errors of whatever it last validated, so
  registering one as a container singleton, holding it statically, or keeping
  it alive across requests in a long-running runtime (Swoole, RoadRunner,
  FrankenPHP) can expose one user's data in another user's response
- Documented the optional dependencies of five constraints, which previously
  threw `LogicException` with no explanation: `Bic`, `Country`, `Currency` and
  `Language` need `symfony/intl`, and `Video` needs `symfony/process` plus
  FFmpeg installed on the server. Also declared under `suggest` in
  `composer.json`
- `tools/check-docs.php`, which executes every PHP example in the
  documentation in a child process. Wired into `composer check` and CI so the
  examples cannot drift from the implementation
- Packagist, Tests and PHPStan badges in the README

### Changed

- Raised the `symfony/validator` floor from `^8` to `^8.1`, and added an
  explicit `symfony/translation-contracts: ^3.5` requirement. On the previous
  floor Composer could resolve `translation-contracts` v2.5, whose
  `TranslatorTrait` uses implicitly nullable parameters — removed in PHP 8.4
  and reported as deprecations on every supported PHP version. Note that
  `symfony/validator ^8.1` still permits `translation-contracts ^2.5|^3`, so
  raising the validator floor alone does not avoid this; the explicit
  constraint is what pins a deprecation-free resolution
- `Errors` and `ValidatedInput` now implement `IteratorAggregate` instead of
  `Iterator`
- `Errors::$errors` visibility changed from `public` to `protected`
- `macro()` now requires `Closure` parameter (was `callable`)
- `requiredIf()` uses strict null/empty-string check instead of `empty()`
- `setData()` resets validation state (errors and validated data cleared)
- `CustomConstraintValidator` safely handles non-scalar values
- Documentation uses `Rule::bail()` instead of `Sequentially`

### Security

- **Fail-open validation when a rule's attribute was not retained.** Input was
  filtered to the declared attribute list before rules ran, so any rule whose
  attribute was missing from that list validated `null` instead of the real
  value. Because most constraints (`Email`, `Length`, `Choice`, `Positive`)
  accept `null`, such rules silently passed on data they never inspected. This
  affected dot-notation and wildcard rules used without the third constructor
  argument, and any mismatch between a rule key and the attribute list.
  Attributes are now derived from rule keys when not declared explicitly, and a
  rule naming an attribute outside an explicit list throws
  `InvalidArgumentException` rather than passing silently.
- **Conditional rules leaked across runs.** `sometimes()` wrote into the shared
  rule set, so a rule applied when its condition was true stayed applied to
  every later `setData()` / `validate()` on the same instance, even once the
  condition was false. Conditional rules are now resolved into a per-run copy.
- **Shared rule state leaked between fields and requests.** `ValidationRule`
  recorded the value and failure message on the constraint itself, so one
  instance reused across fields, runs, or requests reported another field's
  message and permanently lost its default. Validation now runs against a clone
  and restores the pristine message on each run — important under Swoole,
  RoadRunner, and FrankenPHP, where constraints outlive a request.

### Fixed

- `passes()` / `fails()` used "no validated data" as the cache sentinel. When
  every attribute failed, that state is indistinguishable from "never ran", so
  each call silently re-validated and re-fired `after()` hooks; conversely,
  rules added after a passing run were ignored. Replaced with an explicit flag
  that resets on `setData()`, `addRule()`, `after()`, and `sometimes()`.
- `messages()` overrides no longer repeat the same custom message once per
  violation, and now apply to wildcard-expanded keys (`items.*.name`).
- `Errors::add()` no longer collapses distinct violations that share wording;
  only exact repeat messages are deduplicated.
- `Rule::requiredIf()` evaluates a callable condition at validation time rather
  than at construction time.
- Iterator bug: empty collections no longer yield a phantom iteration
- `ValidationRule::$passed` now resets on each `performValidation()` call
- `validate()` no longer accumulates errors/data on repeated calls
- `addRule()` for new attributes no longer triggers undefined key warning
- Replaced deprecated `get_class()` with `static::class`
- Removed backslash-prefixed global function calls
- 21 documentation examples that could not run as written — missing `use`
  imports, undefined variables, and references to classes that were never
  declared. Found by the new documentation checker

### Removed

- `Validator::extend()` (dead code with no consumer)
- `ValidatedInput::$hasNext` and `Errors::$hasNext` properties (replaced by
  `IteratorAggregate`)

[Unreleased]: https://github.com/sim-soft/validator/compare/4.0.0...HEAD
[4.0.0]: https://github.com/sim-soft/validator/compare/3.0.3...4.0.0
