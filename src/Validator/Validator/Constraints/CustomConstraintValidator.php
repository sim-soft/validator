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

        $constraint->withValue($value);

        if ($constraint->performValidation()) {
            return;
        }

        $displayValue = match (true) {
            $value === null => '',
            is_scalar($value) => (string)$value,
            default => gettype($value),
        };

        $this->context->buildViolation($constraint->getFailMessage())
            ->setParameter('{{ value }}', $displayValue)
            ->addViolation();
    }
}
