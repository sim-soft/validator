<?php

namespace Simsoft\Validator\Constraints;

use Closure;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Custom class
 *
 * Closure-based custom constraint for inline validation rules.
 */
class Custom extends ValidationRule
{
    /** @var string Error message */
    public string $message = 'Invalid: {{ value }}.';

    /** @var Closure The callback which performs the validation */
    public Closure $callback;

    /**
     * Constructor.
     *
     * @param string|array|callable $options Callable, array with 'callback' key, or string message.
     * @param callable|null $callback Callback when $options is a string message.
     * @param array|null $groups Validation groups.
     * @param mixed|null $payload Custom payload.
     */
    public function __construct(
        string|array|callable $options,
        ?callable $callback = null,
        ?array    $groups = null,
        mixed $payload = null
    ) {
        if (is_callable($options)) {
            $this->callback = $options instanceof Closure ? $options : Closure::fromCallable($options);
        } elseif (is_array($options)) {
            $this->message = $options['message'] ?? $this->message;
            $cb = $options['callback'] ?? null;
            if (is_callable($cb)) {
                $this->callback = $cb instanceof Closure ? $cb : Closure::fromCallable($cb);
            }
            unset($options['message'], $options['callback']);
        } elseif (is_string($options)) {
            $this->message = $options;
            if (is_callable($callback)) {
                $this->callback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
            }
        }

        if (!isset($this->callback)) {
            throw new InvalidArgumentException(
                sprintf('The "callback" option must be a valid callable ("%s" given).', get_debug_type($callback))
            );
        }

        parent::__construct(null, $groups, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(mixed $value, Closure $fail): void
    {
        ($this->callback)($value, $fail);
    }
}
