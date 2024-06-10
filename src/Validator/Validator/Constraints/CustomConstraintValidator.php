<?php

namespace Simsoft\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * CustomConstraintValidator class
 *
 * The custom constraint validator used for validate the custom constraint.
 */
class CustomConstraintValidator extends ConstraintValidator
{
    /**
     * Validate constraint.
     *
     * @param mixed $value The value
     * @param Constraint $constraint The custom constraint.
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($constraint instanceof ValidationRule) {
            $constraint->withValue($value);
            if (!$constraint->performValidation()) {
                $this->context->buildViolation($constraint->getFailMessage())
                    ->setParameter('{{ value }}', $value === null ? '' : $value)
                    ->addViolation();
            }
        }
    }
}
