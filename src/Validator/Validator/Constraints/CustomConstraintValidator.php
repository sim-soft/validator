<?php

namespace Simsoft\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * CustomConstraintValidator class
 *
 * Bridges ValidationRule subclasses into Symfony's constraint validation system.
 */
class CustomConstraintValidator extends ConstraintValidator
{
    /**
     * Validate a constraint against a value.
     *
     * @param mixed $value The value being validated.
     * @param Constraint $constraint The constraint to validate.
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidationRule) {
            return;
        }

        // Validate against a clone. A ValidationRule records the value and the
        // failure message on itself, so validating the shared instance directly
        // would leak that state to every other field using the same rule object,
        // to later runs, and — in long-running runtimes — across requests.
        $rule = clone $constraint;
        $rule->withValue($value);

        if ($rule->performValidation()) {
            return;
        }

        $displayValue = match (true) {
            $value === null => '',
            is_scalar($value) => (string)$value,
            default => gettype($value),
        };

        $this->context->buildViolation($rule->getFailMessage())
            ->setParameter('{{ value }}', $displayValue)
            ->addViolation();
    }
}
