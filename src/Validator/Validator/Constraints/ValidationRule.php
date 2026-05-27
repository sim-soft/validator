<?php

namespace Simsoft\Validator\Constraints;

use Closure;
use Symfony\Component\Validator\Constraint;

/**
 * ValidationRule class
 *
 * The simplified custom constraint class for creating reusable validation rules.
 */
abstract class ValidationRule extends Constraint
{
    /** @var string The error message */
    public string $message = 'Invalid {{ value }}.';

    /** @var mixed The value being validated */
    protected mixed $value = null;

    /** @var bool Whether the constraint passed validation */
    protected bool $passed = true;

    /**
     * Perform validation.
     *
     * @param mixed $value The value to validate.
     * @param Closure $fail Closure to call with error message on failure.
     * @return void
     */
    abstract public function validate(mixed $value, Closure $fail): void;

    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
    {
        return CustomConstraintValidator::class;
    }

    /**
     * Set the value to be validated.
     *
     * @param mixed $value The value to validate.
     * @return static
     */
    public function withValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Execute validation and return the result.
     *
     * @return bool TRUE if validation passed, FALSE otherwise.
     */
    public function performValidation(): bool
    {
        $this->passed = true;

        $this->validate($this->value, function (string $message): void {
            $this->setFailMessage($message);
            $this->passed = false;
        });

        return $this->passed;
    }

    /**
     * Set fail message.
     *
     * @param string $message Fail message.
     * @return void
     */
    public function setFailMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get fail message.
     *
     * @return string
     */
    public function getFailMessage(): string
    {
        return $this->message;
    }
}
