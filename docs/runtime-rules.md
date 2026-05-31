# Runtime Rules & Configuration

## Adding Rules at Runtime

Add constraints to the validator after creation with `addRule()`.

```php
use Simsoft\Validator;
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make($_POST, [], ['email', 'password']);

$validator->addRule('password', Rule::bail([
    new NotBlank(message: 'Password is required'),
    new Length(
        min: 8,
        max: 20,
        minMessage: 'Minimum {{ limit }} characters are required',
        maxMessage: 'Maximum {{ limit }} characters exceeded',
    ),
]));

if ($validator->fails()) {
    echo $validator->errors()->first('password');
}
```

## Stop on First Failure

Stop validating remaining attributes after the first failure is found.

```php
$validator = Validator::make($inputs, $rules);
$validator->stopOnFirstFailure();

if ($validator->fails()) {
    // Only the first failing attribute will have an error
    echo $validator->errors()->first('email');
}
```
