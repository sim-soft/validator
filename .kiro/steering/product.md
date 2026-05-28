# Product Overview

Simsoft Validator is a PHP library that wraps Symfony's Validator component with
a Laravel-inspired API. It provides a simpler, more ergonomic interface for
validating user input while leveraging Symfony's constraint system under the
hood.

## Key Capabilities

- Validate arrays of input data against constraint rules
- Support for inline validation via `Validator::make()` and reusable custom
  validator classes
- Custom rule creation via closures (`Rule::make()`) or dedicated constraint
  classes extending `ValidationRule`
- Validated input retrieval with `only()`, `except()`, and iteration support
- Error collection with per-attribute first/all message access
- Validation groups and group sequences (Symfony feature)
- Runtime rule addition via `addRule()`
- Conditional required fields via `Rule::requiredIf()`
- Extensible via `Validator::extend()` for named rules and `macro()` for
  instance methods
