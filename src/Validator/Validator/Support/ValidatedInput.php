<?php

namespace Simsoft\Validator\Support;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * ValidatedInput class
 *
 * Holds validated data and provides methods to retrieve subsets.
 */
class ValidatedInput implements IteratorAggregate, Countable
{
    /**
     * Constructor.
     *
     * @param array $data Validated data.
     */
    public function __construct(protected array $data = [])
    {
    }

    /**
     * Add a validated attribute value.
     *
     * @param string $attribute Attribute name.
     * @param mixed $value Validated value.
     * @return void
     */
    public function add(string $attribute, mixed $value): void
    {
        $this->data[$attribute] = $value;
    }

    /**
     * Retrieve all validated data.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get a single validated value by attribute name.
     *
     * @param string $attribute Attribute name.
     * @return mixed
     */
    public function get(string $attribute): mixed
    {
        return $this->data[$attribute] ?? null;
    }

    /**
     * Retrieve only the specified attributes.
     *
     * @param array $attributes Attribute names to include.
     * @return array
     */
    final public function only(array $attributes): array
    {
        return $attributes
            ? array_intersect_key($this->data, array_flip($attributes))
            : $this->data;
    }

    /**
     * Retrieve all attributes except the specified ones.
     *
     * @param array $attributes Attribute names to exclude.
     * @return array
     */
    final public function except(array $attributes): array
    {
        return $attributes
            ? array_diff_key($this->data, array_flip($attributes))
            : $this->data;
    }

    /**
     * Whether the validated data is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /**
     * Check if an attribute exists in the validated data.
     *
     * @param string $attribute Attribute name.
     * @return bool
     */
    public function has(string $attribute): bool
    {
        return array_key_exists($attribute, $this->data);
    }

    /**
     * Get the number of validated attributes.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get all validated data as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Reset all validated data.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * {@inheritDoc}
     *
     * @return Traversable<string, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
