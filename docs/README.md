# Simsoft Validator

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4.svg?logo=php&logoColor=white)](https://www.php.net/)

> A lightweight, framework-independent PHP validation library wrapping Symfony
> Validator with a Laravel-inspired API.

## Why Simsoft Validator?

- **Laravel-style API** — `passes()`, `fails()`, `validated()`, `safe()`
- **Symfony's 70+ constraints** — Email, Url, Uuid, Regex, Length, NotBlank, and
  more
- **Custom rules** — closures or reusable constraint classes
- **Nested & wildcard validation** — `address.city`, `items.*.name`
- **Validation groups** — apply different rules per context
- **After hooks** — cross-field validation (e.g., password confirmation)
- **No framework coupling** — works in any PHP 8.4+ project

## Quick Example

```php
use Simsoft\Validator;
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make($_POST, [
    'email' => Rule::bail([
        new NotBlank(message: 'Email is required'),
        new Email(message: 'Invalid email'),
    ]),
    'password' => new NotBlank(message: 'Password is required'),
]);

if ($validator->passes()) {
    $data = $validator->validated();
} else {
    echo $validator->errors()->first('email');
}
```

## Requirements

- PHP >= 8.4
- Symfony Validator ^8

## License

MIT — See [LICENSE](https://github.com/sim-soft/validator/blob/main/LICENSE) for
details.
