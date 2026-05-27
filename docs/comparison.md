# Comparison with Other PHP Validators

## Overview

|                    | **Simsoft Validator**                      | **Laravel Validation**                   | **Symfony Validator** (standalone)             | **Respect/Validation** | **rakit/validation**               |
|--------------------|--------------------------------------------|------------------------------------------|------------------------------------------------|------------------------|------------------------------------|
| Approach           | Wrapper over Symfony with Laravel-like API | String-based rules (`'required\|email'`) | Constraint objects + PHP attributes            | Fluent chain API       | String-based rules (Laravel clone) |
| Dependencies       | symfony/validator only                     | ~15 illuminate packages                  | None                                           | None                   | None                               |
| PHP requirement    | 8.4+                                       | 8.2+                                     | 8.2+                                           | 8.1+                   | 7.0+                               |
| Rule definition    | Constraint objects                         | String rules or Rule objects             | Constraint objects or PHP attributes           | Chained methods        | String rules                       |
| Custom rules       | Extend `ValidationRule` + closure          | Implement `Rule` interface               | Implement `Constraint` + `ConstraintValidator` | Extend `AbstractRule`  | Closure or class                   |
| Nested/wildcard    | ✅ `items.*.name`                           | ✅ `items.*.name`                         | Via `Collection`/`All` constraints             | ❌                      | ✅                                  |
| Framework coupling | None                                       | Heavy (translator, container)            | None                                           | None                   | None                               |

## Where Simsoft Validator Wins

- **Simpler than raw Symfony** — no manual validator building, violation list
  handling, or constraint validator wiring
- **Lighter than Laravel** — one dependency vs. pulling in half of illuminating
- **Framework-independent** — works anywhere, no service container needed
- **Familiar API** — `passes()`, `fails()`, `validated()`, `safe()` feel natural
  to Laravel developers
- **Full Symfony power** — access to 70+ built-in constraints (Email, Url, Uuid,
  Regex, CardScheme, etc.)
- **Type-safe rules** — IDE autocomplete on constraint constructors, no magic
  strings

## Where Others Win

| Library                | Advantage                                                                                              |
|------------------------|--------------------------------------------------------------------------------------------------------|
| **Laravel Validation** | String rules are more concise (`'required\|email\|max:255'`), massive ecosystem, built-in localization |
| **Respect/Validation** | Fluent API is very readable (`v::email()->notEmpty()->validate($x)`), zero dependencies                |
| **rakit/validation**   | Laravel-style string rules without Laravel's weight, very lightweight                                  |
| **Raw Symfony**        | PHP attribute-based validation on DTOs/entities, deeper integration with Symfony forms                 |

## Target Audience

Simsoft Validator is for developers who want:

1. Laravel's ergonomics (make / passes / fails / validated)
2. Symfony's constraint quality (battle-tested, well-documented)
3. No framework lock-in
4. Object-oriented rule definitions with IDE support

## Closest Alternative

**somnambulist/validation** — a rakit fork with Laravel-style string rules.
Simsoft differentiates by using actual Symfony constraint objects instead of
string-based rules, giving you IDE autocomplete, type safety, and access to the
full Symfony constraint catalog without reimplementing them.
