# Project Structure

```
src/Validator/
├── Validator.php                        # Main Validator class (namespace: Simsoft)
└── Validator/
    ├── Rule.php                         # Static helper for creating custom rules
    ├── Constraints/
    │   ├── ValidationRule.php           # Abstract base for custom constraints
    │   ├── Custom.php                   # Closure-based constraint (extends ValidationRule)
    │   └── CustomConstraintValidator.php # Symfony ConstraintValidator bridge
    └── Support/
        ├── Errors.php                   # Error message collection (implements Iterator)
        └── ValidatedInput.php           # Validated data bag (implements Iterator)

tests/
├── GenericTest.php                      # Tests for inline Validator::make() usage
├── LoginTest.php                        # Tests for custom validator class pattern
├── CustomValidatorTest.php              # Tests for Rule::make() and Rule::requiredIf()
└── Validators/
    ├── LoginValidator.php               # Example custom validator (extends Validator)
    └── Constraints/
        └── Password.php                 # Example custom constraint (extends ValidationRule)
```

## Architecture Patterns

- **Main entry point**: `Simsoft\Validator` — can be used directly via
  `Validator::make()` or subclassed for reusable validators
- **Custom constraints**: Extend `ValidationRule` (abstract), implement
  `validate(mixed $value, Closure $fail)`. The `$fail` closure sets the error
  message.
- **Constraint bridge**: `CustomConstraintValidator` adapts `ValidationRule`
  subclasses to Symfony's `ConstraintValidator` interface
- **Support classes**: `Errors` and `ValidatedInput` are iterable value objects
  for results

## Namespace Mapping

| Namespace                        | Directory                              |
|----------------------------------|----------------------------------------|
| `Simsoft\`                       | `src/Validator/`                       |
| `Simsoft\Validator\`             | `src/Validator/Validator/`             |
| `Simsoft\Validator\Constraints\` | `src/Validator/Validator/Constraints/` |
| `Simsoft\Validator\Support\`     | `src/Validator/Validator/Support/`     |
| `Test\`                          | `tests/Validators/`                    |

## Conventions

- New constraints go in `src/Validator/Validator/Constraints/`
- New support/utility classes go in `src/Validator/Validator/Support/`
- Test files live at the `tests/` root; test helpers (validators, constraints)
  go in `tests/Validators/`
- Tests use PHPUnit `#[DataProvider]` attributes for parameterized test cases
