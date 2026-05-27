# Conditional & Optional Rules

## Conditional Required Fields

Use `Rule::requiredIf()` to make a field required only when a condition is met.
A field is considered empty when its value is `null` or a whitespace-only
string.

```php
use Simsoft\Validator;
use Simsoft\Validator\Rule;

$inputs = $_POST;

$validator = Validator::make($inputs, [
    'password' => Rule::make(function (mixed $value, Closure $fail) {
        if (!preg_match('/^\w+$/', $value)) {
            $fail('Invalid password');
        }
    }),

    // Required when password is not empty
    'password_confirm' => Rule::requiredIf(!empty($inputs['password'])),
]);
```

Alternative forms:

```php
// With a custom message
Rule::requiredIf(!empty($inputs['password']), 'Password confirmation is required')

// With a callable condition (evaluated at rule creation time)
Rule::requiredIf(fn() => !empty($inputs['password']))
```

## Optional Field Validation

Use `Rule::sometimes()` to validate a field only when it has a non-null value.
If the value is `null`, the rule is skipped entirely.

```php
use Simsoft\Validator;
use Simsoft\Validator\Rule;

$validator = Validator::make($_POST, [
    'nickname' => Rule::sometimes(function (mixed $value, Closure $fail) {
        if (strlen($value) < 3) {
            $fail('Nickname must be at least 3 characters');
        }
    }),
]);

// If 'nickname' is null (not provided), validation passes.
// If 'nickname' has a value, the closure runs.
```

## Conditional Rules (Instance Method)

Use the `sometimes()` instance method to conditionally apply rules based on the
input data. The condition closure receives the full input array.

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make($_POST, [
    'role' => new NotBlank(message: 'Role is required'),
], ['role', 'permissions']);

// Only validate 'permissions' when role is 'admin'
$validator->sometimes('permissions', new NotBlank(message: 'Permissions required for admins'), function (array $input) {
    return $input['role'] === 'admin';
});

if ($validator->fails()) {
    echo $validator->errors()->first('permissions');
}
```

This differs from `Rule::sometimes()` (static) which skips null values. The
instance method conditionally adds rules based on arbitrary logic.
