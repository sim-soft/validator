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

## Sequentially vs Array Rules

- `new Sequentially([...])` — stops at the first failing constraint (
  short-circuit)
- `[...]` (plain array) — runs all constraints and collects all violations

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
} else {
    print_r($validator->errors()->all());
}
```

## Available Constraints

The examples above use `NotBlank`, `Email`, and `Length` — but there are many
more. Each constraint is a class you import from
`Symfony\Component\Validator\Constraints`:

```php
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Choice;
// ... and many more
```

Here are the most commonly used ones:

| Constraint | What it checks        | Example                                                  |
|------------|-----------------------|----------------------------------------------------------|
| `NotBlank` | Not null/empty        | `new NotBlank(message: 'Required')`                      |
| `Length`   | String min/max length | `new Length(min: 2, max: 100)`                           |
| `Email`    | Valid email format    | `new Email(message: 'Invalid email')`                    |
| `Url`      | Valid URL             | `new Url(message: 'Invalid URL')`                        |
| `Regex`    | Matches a pattern     | `new Regex(pattern: '/^\d+$/', message: 'Numbers only')` |
| `Range`    | Number within range   | `new Range(min: 1, max: 100)`                            |
| `Choice`   | Value in allowed list | `new Choice(choices: ['active', 'inactive'])`            |
| `Type`     | PHP type check        | `new Type(type: 'integer')`                              |
| `Positive` | Greater than zero     | `new Positive()`                                         |
| `Date`     | Valid date string     | `new Date()`                                             |

Every constraint accepts a `message` parameter to customize the error text, and
a `groups` parameter for [Validation Groups](validation-groups.md).

For the full list of 70+ constraints (numbers, strings, dates, files,
comparison, etc.), see
the [Symfony Constraints Reference](https://symfony.com/doc/current/validation.html#constraints).

## Next Steps

- [Custom Rules with Closures](custom-rules.md)
- [Reusable Custom Constraints](custom-constraints.md)
- [Nested & Wildcard Array Validation](array-validation.md)
- [Conditional Rules](conditional-rules.md)
