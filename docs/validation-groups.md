# Validation Groups

Apply only a subset of constraints by assigning groups. Useful when the same
fields have different rules in different contexts (e.g., login vs.
registration).

```php
use Simsoft\Validator;
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

$validator = Validator::make($_POST, [
    'email' => Rule::bail([
        new NotBlank(message: 'Email is required', groups: ['login', 'register']),
        new Email(message: 'Invalid email', groups: ['login', 'register']),
    ]),
    'password' => [
        new NotBlank(message: 'Password is required', groups: ['login', 'register']),
        new Length(
            min: 8,
            max: 20,
            minMessage: 'Minimum {{ limit }} characters are required',
            maxMessage: 'Maximum {{ limit }} characters exceeded',
            groups: ['login', 'register'],
        ),
        new PasswordStrength(
            minScore: PasswordStrength::STRENGTH_VERY_STRONG,
            groups: ['register'],
        ),
    ],
]);

// Validate only 'login' group constraints
if ($validator->validate('login')) {
    echo 'Pass';
}
```

## Group Sequence

Run groups in order — the next group only runs if the previous one passes.

Here `strict` constraints are only checked once every `login` constraint has
passed, so a blank password reports "Password is required" rather than also
complaining that it is too weak.

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

$validator = Validator::make($_POST, [
    'password' => [
        new NotBlank(message: 'Password is required', groups: ['login']),
        new PasswordStrength(
            minScore: PasswordStrength::STRENGTH_STRONG,
            groups: ['strict'],
        ),
    ],
]);

if ($validator->validate(new GroupSequence(['login', 'strict']))) {
    echo 'Pass';
}
```
