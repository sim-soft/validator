# Validator

Simsoft/Validator is a wrapper for [symfony/validator](https://symfony.com/doc/current/validation.html)

## Define a Validator

```phpt
namespace App\Validators;

use Simsoft\Validator;

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
            'email' => [
                new NotBlank(['message' => 'Email is required']),
                new Email(['message' => 'Invalid email']),
            ],
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
### Simple Usage
```phpt
use App\Validators\LoginValidator;

$validator = new LoginValidator();
$validator->setData($_POST);
if ($validator->validate()) {
    echo 'passed';
    $data = $validator->getData();                          // get all
    $email = $validator->getData('email');                  // get email value only.
    $data = $validator->getOnly(['email', 'password']);     // get only these attributes
    $data = $validator->getAllExcept(['remember_me']);      // get all attributes except 'remember_me'.
} else {
    echo 'failed';
    print_r($validator->getErrors());
}
```
### Define Constraints
You may define constrains as following.
```phpt
$validator = new LoginValidator([
    'email' => [
        new NotBlank(['message' => 'Email is required']),
        new Email([
            'message' => 'Invalid email',
        ]),
    ],
]);

$validator->addRule('password', [
    new NotBlank([
        'message' => 'Password is required',
    ]),
    new Length([
        'min' => 8,
        'max' => 20,
        'minMessage' => 'Minimum {{ limit }} characters are required',
        'maxMessage' => 'Maximum {{ limit }} characters exceeded',
    ]),
]);
```
## Constraints

All supported constraints can be found at [Symfony Validation Constraints](https://symfony.com/doc/current/validation.html#constraints)

## Validation Group
Apply only a subset of the validation constraints
```phpt
namespace App\Validators;

use Simsoft\Validator;

class LoginValidator extends Validator
{
    protected array $attributes ['email', 'password', 'remember_me' => false];

    protected function rules(): array
    {
        return [
            'email' => [
                new NotBlank(['message' => 'Email is required']),
                new Email([
                    'message' => 'Invalid email',
                    'groups' => ['login'],
                ]),
            ],
            'password' => [
                new NotBlank([
                    'message' => 'Password is required',
                    'groups' => ['login'],
                ]),
                new Length([
                    'min' => 8,
                    'max' => 20,
                    'minMessage' => 'Minimum {{ limit }} characters are required',
                    'maxMessage' => 'Maximum {{ limit }} characters exceeded',
                    'groups' => ['login'],
                ]),
            ],
        ];
    }
}

$validator = new LoginValidator();
$validator->setData($_POST);

// Apply those constraints belong to 'login' group only.
if ($validator->validate('login')) {
    echo 'Pass';
} else {
    echo 'Failed';
}
```
### Validation Group Sequence
```phpt
use Symfony\Component\Validator\Constraints\GroupSequence;

$validator = new LoginValidator();
$validator->setData($_POST);

// Apply constraints of group 'login', then constraints of group 'strict'.
if ($validator->validate(new GroupSequence(['login', 'strict']))) {
    echo 'Pass';
} else {
    echo 'Failed';
}
```
## Simple Custom Constraint
Use the simple Custom() class to define simple constraints. The callback method should always return a boolean value.
```phpt
namespace App\Validators;

use Simsoft\Constraints\Custom;
use Simsoft\Validator;

class LoginValidator extends Validator
{
    protected array $attributes ['email', 'password', 'remember_me' => false];

    protected function rules(): array
    {
        return [
            'email' => [
                new NotBlank(['message' => 'Email is required']),
                new Email(['message' => 'Invalid email']),
            ],
            'password' => [
                new Custom(function($value, &$message) {
                    $min = 8;
                    $max = 20;

                    $length = mb_strlen($value, 'UTF-8');

                    if ($length == 0) {
                        $message = 'Password is required';
                        return false;
                    }

                    if ($length < $min) {
                        $message = sprintf('Minimum %d characters are required', $min);
                        return false;
                    }

                    if ($length > $max) {
                        $message = sprintf('Maximum %d characters exceeded', $max);
                        return false;
                    }

                    if (preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/', $value, $matches)) {
                        return true;
                    }

                    $message = 'Invalid password';
                    return false;
                }),

                // OR

                new Custom([
                    'message' => 'Invalid password',
                    'callback' => function($value, &$message) {
                        $min = 8;
                        $max = 20;

                        $length = mb_strlen($value, 'UTF-8');
                        ....
                        return false;
                    },
                    'groups' => ['login'],
                ]),
            ],
        ];
    }
}
```
## Create Reusable Custom Constraint
Every custom constraint class should implement the validate($value) method, and it should always return a boolean value.
```phpt
namespace App\Constraints;

use Simsoft\Constraints\CustomConstraint;

class Password extends CustomConstraint
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

    public function validate($value): bool
    {
        $length = mb_strlen($value, $this->charset);

        if ($length == 0) {
            $this->message = 'Password is required';
            return false;
        }

        if ($length < $this->min) {
            $this->message = sprintf('Minimum %d characters are required', $this->min);
            return false;
        }

        if ($length > $this->max) {
            $this->message = sprintf('Maximum %d characters exceeded', $this->max);
            return false;
        }

        if (preg_match($this->format, $value, $matches)) {
            return true;
        }
        return false;
    }
}
```
Usage of the Password constraint.
```phpt
namespace App\Validators;

use App\Constraints\Password;
use Simsoft\Validator;

class LoginValidator extends Validator
{
    protected array $attributes ['email', 'password', 'remember_me' => false];

    protected function rules(): array
    {
        return [
            'email' => [
                new NotBlank(['message' => 'Email is required']),
                new Email(['message' => 'Invalid email']),
            ],
            'password' => [
                new Password([
                    'message' => 'Invalid password',
                    'min' => 5,
                    'max' => 10,
                    'format' => '/new regex pattern/',
                    'groups' => ['login'],
                ]),
            ],
        ];
    }
}
```

## Advance Custom Validation Constraint
For create advance custom validation constraint, please refer to [How to Create a Custom Validation Constraint](https://symfony.com/doc/current/validation/custom_constraint.html)

## License
The Simsoft Validator is licensed under the MIT License. See the [LICENSE](LICENSE) file for details
