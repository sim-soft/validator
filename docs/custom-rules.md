# Custom Rules with Closures

When none of the [built-in constraints](constraints-reference.md) fit, use
`Rule::make()` to write the check inline.

Your closure receives two arguments: the value being validated, and a `$fail`
callback. Call `$fail('...')` with a message when the value is invalid; return
without calling it and the value passes. There is no `return true` — not
failing *is* passing.

```php
use Closure;
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

Because the closure is ordinary PHP, you can run several checks and report a
different message for each. A rule reports at most one error, and if you call
`$fail()` more than once the last message wins — so use `elseif` to stop at the
first problem you find:

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
