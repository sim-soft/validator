<?php

namespace Test\Constraints;

use Closure;
use Simsoft\Validator\Constraints\ValidationRule;

/**
 * Password class.
 */
class Password extends ValidationRule
{
    public string $message = 'Should be at least 8 alphanumeric characters which include at least 1 uppercase, 1 lowercase, 1 digit and 1 special characters only.';
    protected string $charset = 'UTF-8';
    protected string $format = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/';

    protected int $min;
    protected int $max;

    /**
     * Constructor
     *
     * @param mixed|null $options
     * @param array|null $groups
     * @param mixed|null $payload
     */
    public function __construct(mixed $options = null, array $groups = null, mixed $payload = null)
    {
        $this->min = $options['min'] ?? 8;
        $this->max = $options['max'] ?? 20;
        $this->format = $options['format'] ?? $this->format;
        $this->message = $options['message'] ?? $this->message;

        parent::__construct($options, $groups, $payload);
    }

    /**
     * {@inhericdoc}
     */
    public function validate(mixed $value, Closure $fail): void
    {
        $length = mb_strlen($value, $this->charset);

        if ($length == 0) {
            $fail('Password is required');
        } elseif ($length < $this->min) {
            $fail(sprintf('Minimum %d characters are required', $this->min));
        } elseif ($length > $this->max) {
            $fail(sprintf('Maximum %d characters exceeded', $this->max));
        } elseif (!preg_match($this->format, $value, $matches)) {
            $fail($this->message);
        }
    }
}
