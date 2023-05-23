<?php

namespace Simsoft\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * CustomConstraint class
 *
 * The simplified custom constraint class.
 */
abstract class CustomConstraint extends Constraint
{
    /** @var string The error message */
    public string $message = 'Invalid {{ value }}.';

    /**
     * The validate method
     *
     * @param mixed $value
     * @return bool
     */
    abstract public function validate(mixed $value): bool;

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return CustomConstraintValidator::class;
    }
}
