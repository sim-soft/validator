# Reusable Custom Constraints

For validation logic used across multiple validators, create a constraint class
by extending `ValidationRule`.

> **Important (Symfony Validator v8):** Custom constraints must initialize their
> own properties in the constructor and pass `null` as the first argument to
`parent::__construct()`.

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

Usage:

```php
use App\Constraints\Password;
use Simsoft\Validator;

$validator = Validator::make($_POST, [
    'password' => new Password([
        'message' => 'Invalid password',
        'min' => 5,
        'max' => 10,
    ], groups: ['login']),
]);
```

## Advanced Custom Constraints

For constraints that need access to Symfony's execution context (e.g.,
cross-field validation, database lookups), refer
to [How to Create a Custom Validation Constraint](https://symfony.com/doc/current/validation/custom_constraint.html).
