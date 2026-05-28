# Validation Groups

Apply only a subset of constraints by assigning groups. Useful when the same
fields have different rules in different contexts (e.g., login vs.
registration).

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Sequentially;

$validator = Validator::make($_POST, [
    'email' => new Sequentially([
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

```php
use Symfony\Component\Validator\Constraints\GroupSequence;

$validator = LoginValidator::make($_POST);

if ($validator->validate(new GroupSequence(['login', 'strict']))) {
    echo 'Pass';
}
```
