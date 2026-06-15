# Why Object-Based Rules?

You might wonder why this library uses constraint objects instead of
string-based rules like Laravel's `'required|email|max:255'`.

```php
// String-based (Laravel style)
'email' => 'required|email|max:255'

// Object-based (Simsoft style)
'email' => Rule::bail([
    new NotBlank(message: 'Email is required'),
    new Email(message: 'Invalid email'),
    new Length(max: 255, maxMessage: 'Email too long'),
])
```

## What you gain with objects

- **IDE autocomplete** — your editor shows all available parameters as you type
- **Typo protection** — `new NotBalnk()` is a compiler error; `'not_blank'`
  silently fails at runtime
- **Custom messages inline** — no separate messages file needed
- **Full Symfony catalog** — 70+ constraints work immediately, no mapping layer
- **Named parameters** — `new Length(min: 8, max: 20)` is self-documenting
- **Refactoring** — IDE rename works; find/replace strings doesn't

## What you trade off

- More verbose than `'required|email'`
- Need to import constraint classes

In practice, IDE autocomplete eliminates the verbosity concern — you type
`new Not` and your editor completes the rest with all parameters.

## Performance

String-based rules add a parsing step (split by `|`, resolve rule names to
classes, instantiate). Object-based rules skip all of that — the constraint is
already instantiated.

In practice, the difference is negligible (microseconds per field). The actual
validation logic (regex, DNS lookups, etc.) dominates execution time.

## Bottom line

It's not about performance. It's about developer experience:

- Choose **string rules** if you value brevity and your team knows rule names by
  heart
- Choose **object rules** if you value type safety, IDE support, and zero-config
  access to Symfony's full constraint catalog
