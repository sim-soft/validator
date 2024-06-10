<?php

namespace Simsoft\Validator\Constraints;

use Closure;
use Symfony\Component\Validator\Constraint;

/**
 * ValidationRule class
 *
 * The simplified custom constraint class.
 */
abstract class ValidationRule extends Constraint
{
    /** @var string The error message */
    public string $message = 'Invalid {{ value }}.';

    protected mixed $value;

    /** @var bool Constraint is passed */
    public bool $passed = true;

    /**
     * Perform validation.
     *
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    abstract public function validate(mixed $value, Closure $fail): void;

    /**
     * {@inheritdoc }
     */
    public function validatedBy(): string
    {
        return CustomConstraintValidator::class;
    }

    public function withValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function performValidation(): bool
    {
        $this->validate($this->value, function(string $message) {
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
     * Get fail message
     *
     * @return string
     */
    public function getFailMessage(): string
    {
        return $this->message;
    }
}
