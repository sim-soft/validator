<?php

namespace Simsoft\Validator\Constraints;

use Closure;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Custom class
 *
 * The simple custom class
 */
class Custom extends ValidationRule
{
    /** @var string Error message  */
    public string $message = 'Invalid: {{ value }}.';

    /** @var Closure|null The callback which should always return a boolean value */
    public ?Closure $callback;

    /**
     * Constructor
     *
     * @param string|array|callable $options
     * @param callable|null $callback
     * @param array|null $groups
     * @param mixed|null $payload
     */
    public function __construct(
        string|array|callable $options,
        callable $callback = null,
        array $groups = null,
        mixed $payload = null
    ) {

        if (\is_callable($options)) {
            $this->callback = $options;
            $options = [];
        } elseif (\is_array($options)) {
            $this->message = $options['message'] ?? $this->message;
            $this->callback = $options['callback'] ?? null;
            unset($options['message'], $options['callback']);
        } elseif (\is_string($options)) {
            $this->message = $options;
            $this->callback = $callback;
            $options = [];
        }

        if (!\is_callable($this->callback)) {
            throw new InvalidArgumentException(sprintf('The "callback" option must be a valid callable ("%s" given).', get_debug_type($this->callback)));
        }

        parent::__construct($options, $groups, $payload);
    }

    /**
     * {@inhericdoc}
     */
    public function validate(mixed $value, Closure $fail): void
    {
        $closure = $this->callback;
        $closure($value, $fail);
    }
}
