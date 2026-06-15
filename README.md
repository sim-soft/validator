# Simsoft Validator

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4.svg?logo=php&logoColor=white)](https://www.php.net/)
[![Docs](https://img.shields.io/badge/docs-online-blue.svg)](https://sim-soft.github.io/validator/)

A Laravel-inspired validation wrapper
for [Symfony Validator](https://symfony.com/doc/current/validation.html). Simple
API, full power of Symfony constraints.

**📖 [Full Documentation](https://sim-soft.github.io/validator/)**

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
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make($_POST, [
    // bail: stops at first failure — only one error reported
    'email' => Rule::bail([
        new NotBlank(message: 'Email is required'),
        new Email(message: 'Invalid email'),
    ]),
    // array: runs all constraints — collects all errors
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

**`Rule::bail()` vs array rules:**

- `Rule::bail([...])` — stops at the first failing constraint (short-circuit)
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
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginValidator extends Validator
{
    protected array $attributes = ['email', 'password', 'remember_me' => false];

    protected function rules(): array
    {
        return [
            'email' => Rule::bail([
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

**📖 [Read the Full Documentation](https://sim-soft.github.io/validator/)**

- [Getting Started](https://sim-soft.github.io/validator/#/getting-started)
- [Custom Rules with Closures](https://sim-soft.github.io/validator/#/custom-rules)
- [Reusable Custom Constraints](https://sim-soft.github.io/validator/#/custom-constraints)
- [Validation Groups](https://sim-soft.github.io/validator/#/validation-groups)
- [Nested & Wildcard Array Validation](https://sim-soft.github.io/validator/#/array-validation)
- [Conditional & Optional Rules](https://sim-soft.github.io/validator/#/conditional-rules)
- [After Hooks & Cross-Field Validation](https://sim-soft.github.io/validator/#/after-hooks)
- [Runtime Rules & Configuration](https://sim-soft.github.io/validator/#/runtime-rules)
- [Comparison with Other Validators](https://sim-soft.github.io/validator/#/comparison)

## Available Constraints

All Symfony validation constraints are supported.
See [Symfony Validation Constraints](https://symfony.com/doc/current/validation.html#constraints).

## License

MIT License. See [LICENSE](LICENSE) for details.
