# Security Policy

## Supported Versions

| Version | Supported |
| ------- | --------- |
| 3.x     | Yes       |
| < 3.0   | No        |

## Reporting a Vulnerability

Please report security vulnerabilities privately, **not** through public GitHub
issues.

Use [GitHub's private vulnerability reporting](https://github.com/sim-soft/validator/security/advisories/new)
for this repository. Include:

- A description of the issue and its impact
- Steps to reproduce, ideally a minimal code sample
- Affected version(s)

You can expect an initial response within 7 days.

## Scope

This library validates untrusted input, so the following are treated as
security issues:

- Any input that causes validation to pass when a configured constraint should
  have rejected it (fail-open)
- Rule or attribute configurations that are silently ignored at runtime
- Shared-state leakage between validator instances, fields, or requests

## Notes for Consumers

**Escape error messages before rendering them.** Messages may embed the
submitted value — for example, a rule written as
`$fail("Invalid value: $value")`, or the built-in `{{ value }}` placeholder.
This library does not escape output, because the correct escaping depends on
the context you render into. Apply `htmlspecialchars()` (or your template
engine's escaping) when displaying errors in HTML.

**Declare every attribute you intend to validate.** When you pass an explicit
attribute list, a rule naming an attribute outside that list throws an
`InvalidArgumentException` rather than validating `null` and silently passing.
Do not catch and ignore that exception; it indicates a configuration bug that
would otherwise let unvalidated input through.

**Constraints accept `null` by design.** Following Symfony's semantics, most
constraints (`Email`, `Length`, `Choice`, and others) treat `null` as valid so
that optional fields work. To require a value, add `NotBlank` or `NotNull`
explicitly — do not assume another constraint will reject a missing field.
