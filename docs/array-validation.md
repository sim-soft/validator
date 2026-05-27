# Nested & Wildcard Array Validation

## Nested Dot Notation

Use dot notation to validate nested array values.

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make($_POST, [
    'address.city' => new NotBlank(message: 'City is required'),
    'address.zip' => new Length(min: 5, minMessage: 'Invalid zip code'),
], ['address']);

if ($validator->fails()) {
    echo $validator->errors()->first('address.city');
}
```

Deeply nested paths work too:

```php
$validator = Validator::make($_POST, [
    'user.profile.bio' => new NotBlank(message: 'Bio is required'),
], ['user']);
```

## Wildcard Validation

Use `*` to validate all items in an array. The wildcard expands to match actual
array indices.

```php
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\NotBlank;

$validator = Validator::make([
    'items' => [
        ['name' => 'Widget', 'price' => ''],
        ['name' => '', 'price' => '9.99'],
    ],
], [
    'items.*.name' => new NotBlank(message: 'Item name is required'),
    'items.*.price' => new NotBlank(message: 'Item price is required'),
], ['items']);

if ($validator->fails()) {
    // Errors are keyed by expanded path
    echo $validator->errors()->first('items.0.price'); // "Item price is required"
    echo $validator->errors()->first('items.1.name');  // "Item name is required"
}
```

If the array is empty, no validation is performed for that wildcard rule.
