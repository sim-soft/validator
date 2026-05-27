<?php

namespace Simsoft\Validator\Support;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Errors class
 *
 * Collects and provides access to validation error messages.
 */
class Errors implements IteratorAggregate, Countable
{
    /** @var array<string, array<string>> Error messages grouped by attribute */
    protected array $errors = [];

    /**
     * Constructor.
     *
     * @param array<string, array<string>> $errors Pre-loaded errors.
     */
    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    /**
     * Add an error message for an attribute.
     *
     * @param string $attribute Attribute name.
     * @param string $message Error message.
     * @return static
     */
    public function add(string $attribute, string $message): static
    {
        $this->errors[$attribute][] = $message;
        $this->errors[$attribute] = array_unique($this->errors[$attribute]);
        return $this;
    }

    /**
     * Get the first error message for an attribute.
     *
     * @param string $attribute Attribute name.
     * @return string|null
     */
    public function first(string $attribute): ?string
    {
        return $this->errors[$attribute][0] ?? null;
    }

    /**
     * Determine if an attribute has error messages.
     *
     * @param string $attribute Attribute name.
     * @return bool
     */
    public function has(string $attribute): bool
    {
        return array_key_exists($attribute, $this->errors);
    }

    /**
     * Get all errors as an associative array.
     *
     * @return array<string, array<string>>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Whether the error collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->errors === [];
    }

    /**
     * Get the number of attributes that have errors.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Get all error messages for a specific attribute.
     *
     * @param string $attribute Attribute name.
     * @return Traversable<string>
     */
    public function get(string $attribute): Traversable
    {
        return new ArrayIterator($this->errors[$attribute] ?? []);
    }

    /**
     * Reset all errors.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->errors = [];
    }

    /**
     * Get all errors as an array.
     *
     * Alias for `all()`.
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        return $this->errors;
    }

    /**
     * {@inheritDoc}
     *
     * @return Traversable<string, array<string>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }
}
