# Getting Started

## Installation

```bash
composer require simsoft/validator
```

**Requirements:**

- PHP >= 8.4
- Symfony Validator ^8

## Basic Usage

Use `Validator::make()` to validate an array of input against a set of rules.

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

## `Rule::bail()` vs Array Rules

- `Rule::bail([...])` — stops at the first failing constraint (short-circuit).
  Use when later constraints depend on earlier ones passing (e.g., check not
  blank before checking email format).
- `[...]` (plain array) — runs all constraints and collects all violations. Use
  when you want to show all errors at once.

## Retrieving Validated Data

```php
$validated = $validator->validated();              // all validated data
$email = $validator->validated('email');           // single attribute value

$data = $validator->safe()->only(['email']);       // subset of validated data
$data = $validator->safe()->except(['remember_me']); // exclude specific keys
$data = $validator->safe()->all();                // all validated data

foreach ($validator->safe() as $key => $value) {
    // iterate validated attributes
}
```

## Handling Errors

```php
if ($validator->fails()) {
    // First error for a specific attribute
    echo $validator->errors()->first('email');

    // All errors as an associative array
    $errors = $validator->errors()->all();

    // Count how many attributes have errors
    $count = count($validator->errors());

    // Check if a specific attribute has errors
    if ($validator->errors()->has('email')) {
        // ...
    }

    // Iterate errors for a single attribute
    foreach ($validator->errors()->get('email') as $message) {
        echo $message;
    }

    // Iterate all errors
    foreach ($validator->errors() as $attribute => $messages) {
        foreach ($messages as $message) {
            echo "$attribute: $message";
        }
    }
}
```

## Custom Validator Class

For reusable validation logic, extend the `Validator` class.

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

**The `$attributes` property** defines which input fields the validator expects.
It controls what `setData()` extracts from the input array:

- `'email'` — expects an `email` key, defaults to `null` if missing from input
- `'password'` — expects a `password` key, defaults to `null` if missing
- `'remember_me' => false` — expects a `remember_me` key, defaults to `false` if
  missing

Any input keys not listed in `$attributes` are ignored. If you omit
`$attributes`, the validator infers them from the keys in `rules()`.

```php
// Input: ['email' => 'a@b.com', 'password' => 'secret', 'extra' => 'ignored']
// $validator->all() returns: ['email' => 'a@b.com', 'password' => 'secret', 'remember_me' => false]
```

Usage:

```php
$validator = LoginValidator::make($_POST);

if ($validator->passes()) {
    $data = $validator->validated();
} else {
    print_r($validator->errors()->all());
}
```

## Available Constraints

The examples above use `NotBlank`, `Email`, and `Length` — but there are many
more. See the [Constraints Reference](constraints-reference.md) for a full list
with copy-paste examples.

## Next Steps

- [Constraints Reference](constraints-reference.md)
- [Custom Rules with Closures](custom-rules.md)
- [Reusable Custom Constraints](custom-constraints.md)
- [Nested & Wildcard Array Validation](array-validation.md)
- [Conditional Rules](conditional-rules.md)
