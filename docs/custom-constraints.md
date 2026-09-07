# Reusable Custom Constraints

[`Rule::make()`](custom-rules.md) is the quickest way to write a one-off check.
When the same logic is needed in several validators — or you want to configure
it per use — move it into a class that extends `ValidationRule`.

You implement one method, `validate()`, which receives the value and a `$fail`
callback. Call `$fail('...')` with a message when the value is invalid; return
without calling it and the value passes.

> **Note for Symfony Validator v8:** set up your own properties in the
> constructor and pass `null` as the first argument to `parent::__construct()`.
> Earlier versions let the parent constructor assign options for you; v8 does
> not.

```php
namespace App\Constraints;

use Closure;
use Simsoft\Validator\Constraints\ValidationRule;

class Password extends ValidationRule
{
    public string $message = 'At least 8 characters with uppercase, lowercase, digit, and special character.';
    protected string $charset = 'UTF-8';
    protected string $format = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/';
    protected int $min;
    protected int $max;

    public function __construct(mixed $options = null, ?array $groups = null, mixed $payload = null)
    {
        $this->min = $options['min'] ?? 8;
        $this->max = $options['max'] ?? 20;
        $this->format = $options['format'] ?? $this->format;
        $this->message = $options['message'] ?? $this->message;

        parent::__construct(null, $groups, $payload);
    }

    public function validate(mixed $value, Closure $fail): void
    {
        $length = mb_strlen($value, $this->charset);

        if ($length == 0) {
            $fail('Password is required');
        } elseif ($length < $this->min) {
            $fail(sprintf('Minimum %d characters are required', $this->min));
        } elseif ($length > $this->max) {
            $fail(sprintf('Maximum %d characters exceeded', $this->max));
        } elseif (!preg_match($this->format, $value)) {
            $fail($this->message);
        }
    }
}
```

Usage — the first argument is the options array the constructor above reads, so
every key in it (`min`, `max`, `format`, `message`) is optional:

```php
use App\Constraints\Password;
use Simsoft\Validator;

$validator = Validator::make($_POST, [
    // Defaults: 8–20 characters
    'password' => new Password(),
]);

// Or configured, and limited to a validation group
$validator = Validator::make($_POST, [
    'password' => new Password([
        'message' => 'Invalid password',
        'min' => 5,
        'max' => 10,
    ], groups: ['login']),
]);
```

The same constraint object can be reused across several fields — each field is
validated against its own copy, so a failure on one does not affect another:

```php
use App\Constraints\Password;
use Simsoft\Validator;

$rule = new Password(['min' => 10]);

$validator = Validator::make($_POST, [
    'password' => $rule,
    'password_confirm' => $rule,
]);
```

## Advanced Custom Constraints

For constraints that need access to Symfony's execution context (e.g.,
cross-field validation, database lookups), refer
to [How to Create a Custom Validation Constraint](https://symfony.com/doc/current/validation/custom_constraint.html).
