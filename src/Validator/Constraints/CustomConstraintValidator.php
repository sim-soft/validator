<?php

namespace Simsoft\Constraints;

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
        $valid = true;
        if ($constraint instanceof Custom) {
            $callback = $constraint->callback;
            $valid = $callback($value, $constraint->message);
        } elseif ($constraint instanceof CustomConstraint) {
            $valid = $constraint->validate($value);
        }

        if (!$valid) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value === null ? '' : $value)
                ->addViolation();
        }
    }
}
