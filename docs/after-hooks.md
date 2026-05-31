# After Hooks & Cross-Field Validation

Use `after()` to run logic after all rules have been evaluated. This is useful
for cross-field validation like password confirmation. The hook runs regardless
of whether rules passed or failed — check `validated()` values accordingly.

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make($_POST, [
    'password' => new NotBlank(message: 'Password is required'),
    'password_confirm' => new NotBlank(message: 'Confirmation is required'),
]);

$validator->after(function (Validator $val) {
    $password = $val->validated('password');
    $confirm = $val->validated('password_confirm');

    // Only compare when both fields passed their own rules
    if ($password !== null && $confirm !== null && $password !== $confirm) {
        $val->errors()->add('password_confirm', 'Passwords do not match');
    }
});

if ($validator->fails()) {
    echo $validator->errors()->first('password_confirm');
}
```

## Custom Error Messages

Override the `messages()` method in a custom validator class to replace
constraint messages with your own per-attribute messages. When defined, the
custom message replaces all constraint messages for that attribute.

```php
namespace App\Validators;

use Simsoft\Validator;
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactValidator extends Validator
{
    protected array $attributes = ['email', 'name'];

    protected function rules(): array
    {
        return [
            'email' => Rule::bail([
                new NotBlank(),
                new Email(),
            ]),
            'name' => new NotBlank(),
        ];
    }

    protected function messages(): array
    {
        return [
            'email' => 'Please provide a valid email address.',
            'name' => 'Your name is required.',
        ];
    }
}
```
