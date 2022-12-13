<?php

namespace Test\Constraints;

use Simsoft\Constraints\CustomConstraint;

class Password extends CustomConstraint
{
    public string $message = 'Should be at least 8 alphanumeric characters which include at least 1 uppercase, 1 lowercase, 1 digit and 1 special characters only.';
    protected string $charset = 'UTF-8';
    protected string $format = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/';

    protected int $min;
    protected int $max;

    public function __construct(mixed $options = null, array $groups = null, mixed $payload = null)
    {
        $this->min = $options['min'] ?? 8;
        $this->max = $options['max'] ?? 20;
        $this->format = $options['format'] ?? $this->format;
        $this->message = $options['message'] ?? $this->message;

        parent::__construct($options, $groups, $payload);
    }

    public function validate($value): bool
    {
        $length = mb_strlen($value, $this->charset);

        if ($length == 0) {
            $this->message = 'Password is required';
            return false;
        }

        if ($length < $this->min) {
            $this->message = sprintf('Minimum %d characters are required', $this->min);
            return false;
        }

        if ($length > $this->max) {
            $this->message = sprintf('Maximum %d characters exceeded', $this->max);
            return false;
        }

        if (preg_match($this->format, $value, $matches)) {
            return true;
        }
        return false;
    }
}
