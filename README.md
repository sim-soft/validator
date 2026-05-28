# Simsoft Validator

A Laravel-inspired validation wrapper
for [Symfony Validator](https://symfony.com/doc/current/validation.html). Simple
API, full power of Symfony constraints.

## Requirements

- PHP >= 8.4
- Symfony Validator ^8

## Installation

```sh
composer require simsoft/validator
```

## Basic Usage

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

$validator = Validator::make($_POST, [
    'email' => new Sequentially([
        new NotBlank(message: 'Email is required'),
        new Email(message: 'Invalid email'),
    ]),
    'password' => [
        new NotBlank(message: 'Password is required'),
        new Length(
            min: 8,
            max: 20,
            minMessage: 'Minimum {{ limit }} characters are required',
            maxMessage: 'Maximum {{ limit }} characters exceeded',
        ),
    ],
]);

if ($validator->passes()) {
    $data = $validator->validated();
} else {
    echo $validator->errors()->first('email');
}
```

**`Sequentially` vs array rules:**

- `new Sequentially([...])` — stops at the first failing constraint (
  short-circuit)
- `[...]` (plain array) — runs all constraints and collects all violations

## Retrieving Validated Data

```php
$validated = $validator->validated();              // all validated data
$email = $validator->validated('email');           // single attribute value

$data = $validator->safe()->only(['email']);       // subset of validated data
$data = $validator->safe()->except(['remember_me']); // exclude specific keys

foreach ($validator->safe() as $key => $value) {
    // iterate validated attributes
}
```

## Handling Errors

```php
if ($validator->fails()) {
    echo $validator->errors()->first('email');     // first error for attribute
    $errors = $validator->errors()->all();         // all errors as an array
    $count = count($validator->errors());          // number of failed attributes

    if ($validator->errors()->has('email')) {
        // attribute has errors
    }

    foreach ($validator->errors()->get('email') as $message) {
        echo $message;                            // iterate attribute errors
    }

    foreach ($validator->errors() as $attribute => $messages) {
        // iterate all errors
    }
}
```

## Custom Validator Class

```php
namespace App\Validators;

use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

class LoginValidator extends Validator
{
    protected array $attributes = ['email', 'password', 'remember_me' => false];

    protected function rules(): array
    {
        return [
            'email' => new Sequentially([
                new NotBlank(message: 'Email is required'),
                new Email(message: 'Invalid email'),
            ]),
            'password' => new NotBlank(message: 'Password is required'),
        ];
    }
}
```

```php
$validator = LoginValidator::make($_POST);

if ($validator->passes()) {
    $data = $validator->validated();
}
```

## Documentation

For advanced features, see
the [full documentation](https://sim-soft.github.io/validator/):

- [Custom Rules with Closures](docs/custom-rules.md)
- [Reusable Custom Constraints](docs/custom-constraints.md)
- [Validation Groups](docs/validation-groups.md)
- [Nested & Wildcard Array Validation](docs/array-validation.md)
- [Conditional & Optional Rules](docs/conditional-rules.md)
- [After Hooks & Cross-Field Validation](docs/after-hooks.md)
- [Runtime Rules & Configuration](docs/runtime-rules.md)
- [Comparison with Other Validators](docs/comparison.md)

## Available Constraints

All Symfony validation constraints are supported.
See [Symfony Validation Constraints](https://symfony.com/doc/current/validation.html#constraints).

## License

MIT License. See [LICENSE](LICENSE) for details.
