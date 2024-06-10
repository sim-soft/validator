<?php

namespace Simsoft\Validator\Support;

use Iterator;

/**
 * Errors class.
 */
class Errors implements Iterator
{
    /** @var bool Determine has valid next item. */
    protected bool $hasNext = true;

    /**
     * Constructor.
     *
     * @param array $errors
     */
    public function __construct(public array $errors = [])
    {
    }

    /**
     * Add error
     *
     * @param string $attribute Attribute name.
     * @param string $message Error message.
     * @return $this
     */
    public function add(string $attribute, string $message): static
    {
        $this->errors[$attribute][] = $message;
        $this->errors[$attribute] = array_unique($this->errors[$attribute]);
        return $this;
    }

    /**
     * Get first error message of an attribute.
     *
     * @param string $attribute Attribute name.
     * @return string|null
     */
    public function first(string $attribute): ?string
    {
        return $this->errors[$attribute][0] ?? null;
    }

    /**
     * Determine if an attribute's error message exists.
     *
     * @param string $attribute Attribute name.
     * @return bool
     */
    public function has(string $attribute): bool
    {
        return array_key_exists($attribute, $this->errors);
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Whether errors is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all error messages for an attribute.
     *
     * @param string $attribute Attribute name.
     * @return Iterator
     */
    public function get(string $attribute): Iterator
    {
        if ($this->has($attribute)) {
            foreach ($this->errors[$attribute] as $message) {
                yield $message;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return $this->hasNext;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->hasNext = (bool) next($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function current(): mixed
    {
        return current($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function key(): string|int|null
    {
        return key($this->errors);
    }

}
