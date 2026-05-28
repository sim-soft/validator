<?php

namespace Simsoft\Validator;

use Closure;
use Simsoft\Validator\Constraints\Custom;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Sequentially;

/**
 * Rule class
 *
 * Static helper for creating custom validation rules.
 */
class Rule
{
    /**
     * Create a custom rule with a closure.
     *
     * @param callable $callable Validation callback receiving (mixed $value, Closure $fail).
     * @param array|null $groups Validation groups.
     * @return Constraint
     */
    public static function make(callable $callable, ?array $groups = null): Constraint
    {
        return new Custom($callable, groups: $groups);
    }

    /**
     * Create a conditional required rule.
     *
     * The field is required (must not be null or empty string) only when the condition is true.
     *
     * @param bool|callable $required Condition or callable returning a boolean.
     * @param string $message Error message when validation fails.
     * @param array|null $groups Validation groups.
     * @return Constraint
     */
    public static function requiredIf(
        bool|callable $required,
        string $message = 'This field is required.',
        ?array $groups = null
    ): Constraint
    {
        if (is_callable($required)) {
            $required = $required();
        }

        return new Custom(function (mixed $value, Closure $fail) use ($message, $required): void {
            if (!$required) {
                return;
            }

            if ($value === null) {
                $fail($message);
                return;
            }

            if (is_string($value) && trim($value) === '') {
                $fail($message);
            }
        }, groups: $groups);
    }

    /**
     * Create a rule that only applies when the attribute is present in input.
     *
     * @param callable $callable Validation callback receiving (mixed $value, Closure $fail).
     * @param array|null $groups Validation groups.
     * @return Constraint
     */
    public static function sometimes(callable $callable, ?array $groups = null): Constraint
    {
        return new Custom(function (mixed $value, Closure $fail) use ($callable): void {
            if ($value === null) {
                return;
            }

            $callable($value, $fail);
        }, groups: $groups);
    }

    /**
     * Wrap constraints to stop at the first failure (short-circuit).
     *
     * Equivalent to wrapping in `Sequentially`, but more readable.
     *
     * @param array<Constraint> $constraints Constraints to apply sequentially.
     * @param array|null $groups Validation groups.
     * @return Constraint
     */
    public static function bail(array $constraints, ?array $groups = null): Constraint
    {
        return new Sequentially($constraints, groups: $groups);
    }
}
