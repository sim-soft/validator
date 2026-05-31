# Custom Rules with Closures

Use `Rule::make()` for inline custom validation logic. Call `$fail($message)` to
indicate failure.

```php
use Simsoft\Validator;
use Simsoft\Validator\Rule;

$validator = Validator::make($_POST, [
    'username' => Rule::make(function (mixed $value, Closure $fail) {
        if (!preg_match('/^\w+$/', $value)) {
            $fail('Username must be alphanumeric');
        }
    }),
]);
```

A more complex example with multiple checks:

```php
$validator = Validator::make($_POST, [
    'password' => Rule::make(function (mixed $value, Closure $fail) {
        $length = mb_strlen($value, 'UTF-8');

        if ($length == 0) {
            $fail('Password is required');
        } elseif ($length < 8) {
            $fail('Minimum 8 characters are required');
        } elseif ($length > 20) {
            $fail('Maximum 20 characters exceeded');
        } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/', $value)) {
            $fail('Invalid password');
        }
    }),
]);
```
