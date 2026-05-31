# Constraints Reference

Every rule in Simsoft Validator is a constraint object from [
`Symfony\Component\Validator\Constraints`](https://symfony.com/doc/current/validation.html#constraints).
This page shows how to use the most common ones — copy-paste ready.

## Table of Contents

- [String Constraints](#string-constraints) — NotBlank, Length, Email, Url,
  Regex, Uuid, Ip
- [Number Constraints](#number-constraints) — Range, Positive, PositiveOrZero,
  LessThan, GreaterThan
- [Choice Constraints](#choice-constraints) — Single and multiple selection
- [Type Constraints](#type-constraints) — integer, bool, array
- [Date Constraints](#date-constraints) — Date, DateTime
- [Comparison Constraints](#comparison-constraints) — EqualTo, NotEqualTo
- [Collection Constraints](#collection-constraints) — Count, Unique
- [File Constraints](#file-constraints) — File, Image, Video
- [Boolean Constraints](#boolean-constraints) — IsTrue, IsFalse
- [Financial Constraints](#financial-constraints) — CardScheme, Iban, Bic
- [Locale Constraints](#locale-constraints) — Country, Currency, Language,
  Timezone
- [Password Constraint](#password-constraint) — PasswordStrength
- [Combining Multiple Constraints](#combining-multiple-constraints) — bail vs.
  an array
- [Common Patterns](#common-patterns) — Email, optional, dropdown, numeric, file
  upload

> **See also:
** [Full list of 70+ Symfony Constraints](https://symfony.com/doc/current/validation.html#constraints)

## String Constraints

### NotBlank — Field must not be empty

```php
use Symfony\Component\Validator\Constraints\NotBlank;

'name' => new NotBlank(message: 'Name is required')
```

### NotNull — Value must not be null

Unlike `NotBlank`, this allows empty strings. Use when you need to ensure a
value exists, but it can be empty.

```php
use Symfony\Component\Validator\Constraints\NotNull;

'field' => new NotNull(message: 'This field must be provided')
```

### Blank — Field must be empty

Used for honeypot fields — a hidden form field invisible to real users but
visible to bots. Since bots autofill every field, if this field has a value,
it's a bot.

```html
<!-- Hidden from users via CSS -->
<input type="text" name="website" style="display:none">
```

```php
use Symfony\Component\Validator\Constraints\Blank;

'website' => new Blank(message: 'Spam detected')
```

### Length — Min/max string length

```php
use Symfony\Component\Validator\Constraints\Length;

'username' => new Length(
    min: 3,
    max: 20,
    minMessage: 'Must be at least {{ limit }} characters',
    maxMessage: 'Cannot exceed {{ limit }} characters',
)
```

### Email — Valid email address

```php
use Symfony\Component\Validator\Constraints\Email;

'email' => new Email(message: 'Please enter a valid email')
```

### Url — Valid URL

```php
use Symfony\Component\Validator\Constraints\Url;

'website' => new Url(message: 'Please enter a valid URL')
```

### Regex — Match a pattern

```php
use Symfony\Component\Validator\Constraints\Regex;

// Only letters and numbers
'username' => new Regex(
    pattern: '/^\w+$/',
    message: 'Only alphanumeric characters allowed',
)

// Phone number format
'phone' => new Regex(
    pattern: '/^\+?[\d\s\-()]+$/',
    message: 'Invalid phone number',
)
```

### Uuid — Valid UUID

```php
use Symfony\Component\Validator\Constraints\Uuid;

'token' => new Uuid(message: 'Invalid token format')
```

### Ip — Valid IP address

```php
use Symfony\Component\Validator\Constraints\Ip;

'server_ip' => new Ip(message: 'Invalid IP address')
```

### Json — Valid JSON string

```php
use Symfony\Component\Validator\Constraints\Json;

'config' => new Json(message: 'Must be valid JSON')
```

### Hostname — Valid hostname

```php
use Symfony\Component\Validator\Constraints\Hostname;

'domain' => new Hostname(message: 'Invalid hostname')
```

### CssColor — Valid CSS color

```php
use Symfony\Component\Validator\Constraints\CssColor;

'theme_color' => new CssColor(message: 'Must be a valid CSS color')
```

Accepts: `#ff0000`, `rgb(255, 0, 0)`, `hsl(0, 100%, 50%)`, `red`, etc.

### WordCount — Min/max word count

```php
use Symfony\Component\Validator\Constraints\WordCount;

'bio' => new WordCount(
    min: 10,
    max: 200,
    minMessage: 'Bio must be at least {{ min }} words',
    maxMessage: 'Bio cannot exceed {{ max }} words',
)
```

## Number Constraints

### Range — Number between min and max

```php
use Symfony\Component\Validator\Constraints\Range;

'age' => new Range(
    min: 18,
    max: 120,
    notInRangeMessage: 'Age must be between {{ min }} and {{ max }}',
)
```

### Positive — Greater than zero

```php
use Symfony\Component\Validator\Constraints\Positive;

'price' => new Positive(message: 'Price must be greater than zero')
```

### PositiveOrZero — Zero or greater

```php
use Symfony\Component\Validator\Constraints\PositiveOrZero;

'quantity' => new PositiveOrZero(message: 'Quantity cannot be negative')
```

### LessThan / GreaterThan

```php
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\GreaterThan;

'discount' => new LessThan(value: 100, message: 'Discount must be less than 100%')
'minimum_order' => new GreaterThan(value: 0, message: 'Must be greater than zero')
```

## Choice Constraints

### Choice — Value must be in a list

```php
use Symfony\Component\Validator\Constraints\Choice;

'role' => new Choice(
    choices: ['admin', 'editor', 'viewer'],
    message: 'Invalid role. Choose from: admin, editor, viewer',
)
```

### Choice — Multiple selections allowed

```php
'tags' => new Choice(
    choices: ['php', 'javascript', 'python', 'go'],
    multiple: true,
    min: 1,
    max: 3,
    multipleMessage: 'Select at least one tag',
    maxMessage: 'You cannot select more than {{ limit }} tags',
)
```

## Type Constraints

### Type — Check PHP type

```php
use Symfony\Component\Validator\Constraints\Type;

'age' => new Type(type: 'integer', message: 'Age must be a number')
'is_active' => new Type(type: 'bool', message: 'Must be true or false')
'scores' => new Type(type: 'array', message: 'Scores must be an array')
```

## Date Constraints

### Date — Valid date string (Y-m-d)

Accepts values like `2024-01-15`. Does not accept time.

```php
use Symfony\Component\Validator\Constraints\Date;

'birthday' => new Date(message: 'Must be a valid date (YYYY-MM-DD)')
```

### DateTime — Valid date with time (Y-m-d H:i:s)

Accepts values like `2024-01-15 14:30:00`. Use when you need both date and time.

```php
use Symfony\Component\Validator\Constraints\DateTime;

'event_at' => new DateTime(message: 'Must be a valid datetime (YYYY-MM-DD HH:MM:SS)')
```

| Constraint | Accepts     | Example value         |
|------------|-------------|-----------------------|
| `Date`     | Date only   | `2024-01-15`          |
| `DateTime` | Date + time | `2024-01-15 14:30:00` |

## Comparison Constraints

### EqualTo / NotEqualTo

```php
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotEqualTo;

'country' => new EqualTo(value: 'MY', message: 'Only Malaysia is supported')
'status' => new NotEqualTo(value: 'deleted', message: 'Cannot use deleted status')
```

## Collection Constraints

### Count — Array item count min/max

```php
use Symfony\Component\Validator\Constraints\Count;

'tags' => new Count(
    min: 1,
    max: 5,
    minMessage: 'Select at least {{ limit }} tag',
    maxMessage: 'Cannot select more than {{ limit }} tags',
)
```

### Unique — All array values must be unique

```php
use Symfony\Component\Validator\Constraints\Unique;

'categories' => new Unique(message: 'Each category must be unique')
```

## Boolean Constraints

### IsTrue — Value must be true

Commonly used for "agree to terms" checkboxes.

```php
use Symfony\Component\Validator\Constraints\IsTrue;

'terms_accepted' => new IsTrue(message: 'You must accept the terms and conditions')
```

### IsFalse — Value must be false

```php
use Symfony\Component\Validator\Constraints\IsFalse;

'is_spam' => new IsFalse(message: 'This submission was flagged as spam')
```

## File Constraints

### File — Validate an uploaded file (type and size)

```php
use Symfony\Component\Validator\Constraints\File;

// Single file upload
'resume' => new File(
    maxSize: '2M',
    mimeTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    mimeTypesMessage: 'Please upload a PDF or Word document',
    maxSizeMessage: 'File is too large. Maximum size is {{ limit }}{{ suffix }}.',
)
```

### Image — Validate image file (type, size, dimensions)

```php
use Symfony\Component\Validator\Constraints\Image;

// Profile photo
'avatar' => new Image(
    maxSize: '5M',
    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
    mimeTypesMessage: 'Please upload a JPEG, PNG, or WebP image',
    maxWidth: 2000,
    maxHeight: 2000,
    maxWidthMessage: 'Image width cannot exceed {{ max_width }}px',
    maxHeightMessage: 'Image height cannot exceed {{ max_height }}px',
)

// Thumbnail with exact dimensions
'thumbnail' => new Image(
    maxSize: '1M',
    minWidth: 200,
    minHeight: 200,
    maxWidth: 500,
    maxHeight: 500,
)
```

### Video — Validate a video file

```php
use Symfony\Component\Validator\Constraints\Video;

'video' => new Video(
    maxSize: '100M',
    mimeTypes: ['video/mp4', 'video/webm', 'video/quicktime'],
    mimeTypesMessage: 'Please upload an MP4, WebM, or MOV video',
)
```

### File — Common MIME types reference

| File type     | MIME types                                                                |
|---------------|---------------------------------------------------------------------------|
| PDF           | `application/pdf`                                                         |
| Word (.doc)   | `application/msword`                                                      |
| Word (.docx)  | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` |
| Excel (.xlsx) | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`       |
| CSV           | `text/csv`                                                                |
| JPEG          | `image/jpeg`                                                              |
| PNG           | `image/png`                                                               |
| WebP          | `image/webp`                                                              |
| SVG           | `image/svg+xml`                                                           |
| ZIP           | `application/zip`                                                         |
| MP4           | `video/mp4`                                                               |
| WebM          | `video/webm`                                                              |
| MOV           | `video/quicktime`                                                         |
| AVI           | `video/x-msvideo`                                                         |

## Financial Constraints

### CardScheme — Credit card number format

```php
use Symfony\Component\Validator\Constraints\CardScheme;

'card_number' => new CardScheme(
    schemes: [CardScheme::VISA, CardScheme::MASTERCARD, CardScheme::AMEX],
    message: 'Invalid credit card number',
)
```

### Iban — Valid IBAN (bank account)

```php
use Symfony\Component\Validator\Constraints\Iban;

'bank_account' => new Iban(message: 'Invalid IBAN number')
```

### Bic — Valid BIC/SWIFT code

```php
use Symfony\Component\Validator\Constraints\Bic;

'swift_code' => new Bic(message: 'Invalid BIC/SWIFT code')
```

## Locale Constraints

### Country — Valid ISO 3166-1 country code

```php
use Symfony\Component\Validator\Constraints\Country;

'country' => new Country(message: 'Invalid country code')
```

Accepts: `US`, `GB`, `MY`, `SG`, etc.

### Currency — Valid ISO 4217 currency code

```php
use Symfony\Component\Validator\Constraints\Currency;

'currency' => new Currency(message: 'Invalid currency code')
```

Accepts: `USD`, `EUR`, `MYR`, `SGD`, etc.

### Language — Valid language code

```php
use Symfony\Component\Validator\Constraints\Language;

'language' => new Language(message: 'Invalid language code')
```

Accepts: `en`, `zh`, `ms`, `fr`, etc.

### Timezone — Valid timezone identifier

```php
use Symfony\Component\Validator\Constraints\Timezone;

'timezone' => new Timezone(message: 'Invalid timezone')
```

Accepts: `Asia/Kuala_Lumpur`, `America/New_York`, `UTC`, etc.

## Password Constraint

### PasswordStrength — Check password complexity

```php
use Symfony\Component\Validator\Constraints\PasswordStrength;

'password' => new PasswordStrength(
    minScore: PasswordStrength::STRENGTH_MEDIUM,
    message: 'Password is too weak. Use a mix of letters, numbers, and symbols.',
)
```

Strength levels: `STRENGTH_WEAK`, `STRENGTH_MEDIUM`, `STRENGTH_STRONG`,
`STRENGTH_VERY_STRONG`

## Combining Multiple Constraints

Use `Rule::bail()` to stop at the first failure, or an array to collect all
errors:

```php
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

// Stop at first failure
'email' => Rule::bail([
    new NotBlank(message: 'Email is required'),
    new Length(max: 255, maxMessage: 'Email too long'),
    new Email(message: 'Invalid email format'),
])

// Collect all errors
'password' => [
    new NotBlank(message: 'Password is required'),
    new Length(min: 8, minMessage: 'At least 8 characters'),
    new PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM),
]
```

## Common Patterns

### Required email field

```php
'email' => Rule::bail([
    new NotBlank(message: 'Email is required'),
    new Email(message: 'Invalid email'),
])
```

### Optional field with validation when present

```php
use Simsoft\Validator\Rule;

'nickname' => Rule::sometimes(function (mixed $value, Closure $fail) {
    if (strlen($value) < 3) {
        $fail('Nickname must be at least 3 characters');
    }
})
```

### Dropdown / select a field

```php
'country' => Rule::bail([
    new NotBlank(message: 'Please select a country'),
    new Choice(choices: ['US', 'UK', 'MY', 'SG'], message: 'Invalid country'),
])
```

### Numeric input with range

```php
'quantity' => Rule::bail([
    new NotBlank(message: 'Quantity is required'),
    new Type(type: 'numeric', message: 'Must be a number'),
    new Range(min: 1, max: 999, notInRangeMessage: 'Must be between 1 and 999'),
])
```

### File upload

```php
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

'document' => Rule::bail([
    new NotBlank(message: 'Please upload a file'),
    new File(
        maxSize: '10M',
        mimeTypes: ['application/pdf'],
        mimeTypesMessage: 'Only PDF files are accepted',
    ),
])
```

## Full Symfony Reference

For the complete list of 70+ constraints, see
the [Symfony Constraints Reference](https://symfony.com/doc/current/validation.html#constraints).
