# Simsoft Validator

Simsoft/Validator is a wrapper for [symfony/validator](https://symfony.com/doc/current/validation.html) and inspired by Laravel validator.

## Install
```sh
composer require simsoft/validator
```

## Basic Usage
```php

require 'vendor/autoload.php';

use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

$inputs = $_POST;

$validator = Validator::make($inputs, [
    'email' => new Sequentially([
        new NotBlank(message: 'Email is required'),
        new Email(message: 'Invalid email'),
    ]),
    'password' => [
        new NotBlank(message: 'Password is required'),
        new Length([
            'min' => 8,
            'max' => 20,
            'minMessage' => 'Minimum {{ limit }} characters are required',
            'maxMessage' => 'Maximum {{ limit }} characters exceeded',
        ]),
    ],
]);

if ($validator->passes()) {
    echo 'passed';
    $validated = $validator->validated();                       // get all validated data.
    $email = $validator->validated('email');                    // get email value only.
    $data = $validator->safe()->only(['email', 'password']);    // get only these attributes
    $data = $validator->safe()->except(['remember_me']);        // get all attributes except 'remember_me'.
    $validated = $validator->safe()->all();                     // get all validated data.

    foreach($validator->safe() as $key => $value) {
        ...
    }

} elseif ($valildator->fails()) {
    echo 'failed';

    echo $validator->errors()->first('email');  // Display the email first error message.
    $errors = $validator->errors()->all();      // Retrieve array of all error messages.

    // loop through all 'email' error messages.
    foreach($validator->errors()->get('email') as $message) {
        ...
    }

    // Loop through all error messages.
    foreach($validator->errors() as $key => $messages) {
        foreach($messages as $message) {
            ...
        }
    }
}
```

## Custom Validator
```php
namespace App\Validators;

use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

class LoginValidator extends Validator
{
    /** @var array Expecting attributes and its default value */
    protected array $attributes ['email', 'password', 'remember_me' => false];

    /**
     * Define the validation rules.
     *
     * @return array<mixed> The validation rules.
     */
    protected function rules(): array
    {
        return [
            'email' => new Sequentially([
                new NotBlank(['message' => 'Email is required']),
                new Email(['message' => 'Invalid email']),
            ]),
            'password' => [
                new NotBlank(['message' => 'Password is required']),
                new Length([
                    'min' => 8,
                    'max' => 20,
                    'minMessage' => 'Minimum {{ limit }} characters are required',
                    'maxMessage' => 'Maximum {{ limit }} characters exceeded',
                ]),
            ],
        ];
    }
}
```
### Example Usage of Custom Validator
```php
use App\Validators\LoginValidator;

$inputs = $_POST;

$validator = LoginValidator::make($inputs);
if ($validator->passes()) {
    echo 'passed';
    $data = $validator->validated();
} else {
    echo 'failed';
    print_r($validator->errors()->all());
}
```
## Add rule at Runtime
You may define constrains as following.
```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

$validator = Validator::make($inputs);

$validator->addRule('password', new Sequentially([
    new NotBlank(['message' => 'Password is required']),
    new Length([
        'min' => 8,
        'max' => 20,
        'minMessage' => 'Minimum {{ limit }} characters are required',
        'maxMessage' => 'Maximum {{ limit }} characters exceeded',
    ]),
]));
```
## Constraints

All supported constraints can be found at [Symfony Validation Constraints](https://symfony.com/doc/current/validation.html#constraints)

## Validation Group
Apply only a subset of the validation constraints
```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Sequentially;

$inputs = $_POST;

$validator = Validator::make($inputs, [
    'email' => new Sequentially([
        new NotBlank(['message' => 'Email is required', 'groups' => ['login', 'register']),
        new Email([
            'message' => 'Invalid email',
            'groups' => ['login', 'register'],
        ]),
    ]),
    'password' => [
        new NotBlank([
            'message' => 'Password is required',
            'groups' => ['login', 'register'],
        ]),
        new Length([
            'min' => 8,
            'max' => 20,
            'minMessage' => 'Minimum {{ limit }} characters are required',
            'maxMessage' => 'Maximum {{ limit }} characters exceeded',
            'groups' => ['login', 'register'],
        ]),
        new PasswordStrength([
            'minScore' => PasswordStrength::STRENGTH_VERY_STRONG,
            'groups' => ['register'],
        ])
    ],
]);

// Apply those constraints belong to 'login' group only.
if ($validator->validate('login')) {
    echo 'Pass';
} else {
    echo 'Failed';
}
```
### Validation Group Sequence
```php
use Symfony\Component\Validator\Constraints\GroupSequence;

$validator = LoginValidator::make($_POST);

// Apply constraints of group 'login', then constraints of group 'strict'.
if ($validator->validate(new GroupSequence(['login', 'strict']))) {
    echo 'Pass';
} else {
    echo 'Failed';
}
```
## Make Custom Rule
Use Simsoft\Validator\Rule class to define simple rule.

```php
namespace App\Validators;

use Closure;
use Simsoft\Validator;
use Simsoft\Validator\Rule;

$inputs = $_POST;

$validator = Validator::make($inputs, [
    // ...
    'password' => Rule::make(function(mixed $value, Closure $fail) {
        $min = 8;
        $max = 20;

        $length = mb_strlen($value, 'UTF-8');

        if ($length == 0) {
            $fail('Password is required');
        } elseif ($length < $min) {
            $fail(sprintf('Minimum %d characters are required', $min));
        } elseif ($length > $max) {
            $fail(sprintf('Maximum %d characters exceeded', $max));
        } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/', $value, $matches)) {
            $fail('Invalid password');
        }
    })
]);
```
## Reusable Custom Validator
The following create a reusable "Password" validation rule.

```php
namespace App\Constraints;

use Closure;
use Simsoft\Validator\Constraints\ValidationRule;

class Password extends ValidationRule
{
    public string $message = 'At least 8 alphanumeric characters which include at least 1 uppercase, 1 lowercase, 1 digit and 1 special characters only.';
    protected string $charset = 'UTF-8';
    protected string $format = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/';

    protected int $min;
    protected int $max;

    public function __construct(mixed $options = null, array $groups = null, mixed $payload = null)
    {
        $this->min = $options['min'] ?? 8;
        $this->max = $options['max'] ?? 20;
        $this->format = $options['format'] ?? $this->format;
        $this->message = $options['message'] ?? $this->message;

        parent::__construct($options, $groups, $payload);
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
        } elseif (!preg_match($this->format, $value, $matches)) {
            $fail($this->message);
        }
    }
}
```
Example usage of Password validation rule.
```php
use App\Constraints\Password;
use Simsoft\Validator;

$input = $_POST;

$validator = Validator::make($input, [
    // ...
    'password' => new Password([
        'message' => 'Invalid password',
        'min' => 5,
        'max' => 10,
        'format' => '/new regex pattern/',
        'groups' => ['login'],
    ]),
])

if ($validator->passes()) {
    echo 'Pass';
} else {
    echo 'Failed';
}
```

## Advance Custom Validation Constraint
For create advance custom validation constraint, please refer to [How to Create a Custom Validation Constraint](https://symfony.com/doc/current/validation/custom_constraint.html)

## Validation Rule Helpers
```php
use Closure;
use Simsoft\Validator;
use Simsoft\Validator\Rule;

$inputs = $_POST;

$validator = Validator::make($inputs, [
    // ...
    'password' => [
        Rule::make(function(mixed $value, Closure $fail) {
            if (!preg_match('/^w+$/', $value, $matches)) {
                $fail('Invalid password');
            }
        })
    ]

    'password_confirm' => [
        Rule::requiredIf(!empty($inputs['password'])),
        // or
        Rule::requiredIf(!empty($inputs['password']), 'Password confirm is required'),
        // or
        Rule::requiredIf(fn() => !empty($inputs['password'])),
    ],
])
```

## License
The Simsoft Validator is licensed under the MIT License. See the [LICENSE](LICENSE) file for details
