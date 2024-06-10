<?php

namespace Simsoft\Validator\Support;

use Iterator;

/**
 *
 */
class ValidatedInput implements Iterator
{
    /** @var bool Determine has valid next item. */
    protected bool $hasNext = true;

    /**
     * Constructor.
     *
     * @param array $data Hold validated data
     */
    public function __construct(protected array $data = [])
    {
    }

    /**
     * Add validated data.
     *
     * @param string $attribute Attribute name.
     * @param mixed $value
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
     * Get value of validated data by attribute name.
     *
     * @param string $attribute Attribute name.
     * @return mixed
     */
    public function get(string $attribute): mixed
    {
        return $this->data[$attribute] ?? null;
    }

    /**
     * Retrieve a portion of the validated inputs.
     *
     * @param array $attributes Inputs to be retrieved.
     * @return array
     */
    final public function only(array $attributes): array
    {
        return $attributes
            ? array_intersect_key($this->data, array_flip($attributes))
            : $this->data;
    }

    /**
     * Retrieve a portion of the validated inputs with exclusions.
     *
     * @param array $attributes Inputs to be excluded.
     * @return array
     */
    final public function except(array $attributes): array
    {
        return $attributes
            ? array_diff_key($this->data, array_flip($attributes))
            : $this->data;
    }

    /**
     * Whether validated data is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Perform rewind.
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->hasNext;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->hasNext = (bool) next($this->data);
    }

    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return current($this->data);
    }

    /**
     * @return string|int|null
     */
    public function key(): string|int|null
    {
        return key($this->data);
    }

}
