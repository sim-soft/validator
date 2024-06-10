<?php

namespace Simsoft\Validator;

use Closure;
use Simsoft\Validator\Constraints\Custom;
use Symfony\Component\Validator\Constraint;

/**
 * Rule class
 */
class Rule
{
    /**
     * Make custom rule with closure.
     *
     * @param callable $callable
     * @return Constraint
     */
    public static function make(callable $callable): Constraint
    {
        return new Custom($callable);
    }

    /**
     * @param bool|callable $required
     * @param string $message
     * @return Constraint
     */
    public static function requiredIf(
        bool|callable $required,
        string $message = 'This field is required.',
    ): Constraint
    {
        if (is_callable($required)) {
            $required = $required();
        }

        return new Custom(function(mixed $value, Closure $fail) use ($message, $required) {
            if ($required) {
                if (is_string($value)) {
                    $value = trim($value);
                }

                if (empty($value)) {
                    $fail($message);
                }
            }
        });
    }
}
