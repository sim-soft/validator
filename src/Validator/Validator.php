<?php

namespace Simsoft;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Class Validator
 *
 * The Validator class is used to validate user input.
 *
 * @package Simsoft
 *
 */
class Validator
{
    /** @var array Errors */
    protected array $errors = [];

    /** @var array Define expecting attributes */
    protected array $attributes = [];

    /** @var array User input */
    private array $input = [];

    /** @var string|GroupSequence|array<string|GroupSequence>|null Validation group */
    protected string|GroupSequence|array|null $group = null;

    /** @var array  Callback to be called before validation */
    protected array $closures = [];

    /**
     * Constructor.
     *
     * @param array $rules Validation rules
     */
    public function __construct(protected array $rules = [])
    {
        if (empty($this->attributes) && $this->rules()) {
            $this->attributes = array_keys($this->rules());
        }

        if ($this->rules) {
            $this->attributes = array_merge($this->attributes, array_keys($this->rules));
        }
    }

    /**
     * Get validation object by make rules
     *
     * @return $this;
     */
    public static function make(array $rules = []): static
    {
        return new static($rules);
    }

    /**
     * Are there any errors?
     *
     * @return bool TRUE if there are errors, FALSE otherwise.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get All the errors.
     *
     * @return array The errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate the input.
     *
     * @param string|GroupSequence|array|null $group The validation groups to validate. If none is given, "Default" is assumed
     * @return bool TRUE if the input is valid, FALSE otherwise.
     */
    final public function validate(string|GroupSequence|array|null $group = null): bool
    {
        $this->group = $group;

        $this->rules = array_merge($this->rules, $this->rules());

        $validator = Validation::createValidator();
        foreach($this->rules as $attribute => $rules) {
            $violations = $validator->validate($this->input[$attribute], $rules, $this->group);
            if (count($violations) > 0) {
                $this->errors[$attribute] = $violations->get(0)->getMessage();
            }
        }

        return !$this->hasErrors();
    }

    /**
     * Check the input is valid.
     *
     * @param string|GroupSequence|array|null $group The validation groups to validate. If none is given, "Default" is assumed
     * @return bool TRUE if the input is valid, FALSE otherwise.
     */
    public function passes(string|GroupSequence|array|null $group = null): bool
    {
        return $this->validate($group);
    }

    /**
     * Check the input is invalid.
     *
     * @param string|GroupSequence|array|null $group The validation groups to validate. If none is given, "Default" is assumed
     * @return bool TRUE if the input is invalid, FALSE otherwise.
     */
    public function fails(string|GroupSequence|array|null $group = null): bool
    {
        return !$this->validate($group);
    }

    /**
     * Set input data
     *
     * @param array $input The input to be validated.
     */
    final public function setData(array $input): static
    {
        foreach($this->attributes as $key => $value) {
            if(is_int($key)) {
                $attribute = $value;
                $default = null;
            } else {
                $attribute = $key;
                $default = $value;
            }
            $this->input[$attribute] = array_key_exists($attribute, $input)? $input[$attribute]: $default;
        }

        return $this;
    }

    /**
     * Get all the input values.
     *
     * If an attribute is provided, it will return the value of that attribute only.
     *
     * @param string|null $attribute    The attribute to get the value of.
     * @return mixed
     */
    final public function getData(?string $attribute=null): mixed
    {
        return $attribute
            ? ($this->input[$attribute] ?? null)
            : $this->input;
    }

    /**
     * Get subset of input attributes
     *
     * @param array $attributes Attributes to be retrieved.
     * @return array
     */
    final public function getOnly(array $attributes = []): array
    {
        return $attributes
                ? \array_intersect_key($this->input, \array_flip($attributes))
                : $this->input;
    }

    /**
     * Get subset of inputs except these attributes
     *
     * @param array $attributes Attributes to be excluded.
     * @return array
     */
    final public function getAllExcept(array $attributes = []): array
    {
        return $attributes
                ? \array_diff_key($this->input, \array_flip($attributes))
                : $this->input;
    }

    /**
     * Add constraints rules to an attribute
     *
     * @param string $attribute The attribute name.
     * @param array $rules The array of constraints
     * @return $this
     */
    public function addRule(string $attribute, array $rules = []): static
    {
        if (array_key_exists($attribute, $this->rules)) {
            $this->rules[$attribute] = array_merge($this->rules[$attribute], $rules);
        } else {
            $this->rules[$attribute] = $rules;
        }

        if (!in_array($attribute, $this->attributes)) {
            $this->attributes[] = $attribute;
        }

        return $this;
    }

    /**
     * Define the validation rules.
     *
     * @return array The validation rules.
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Add additional method implementation.
     *
     * @param string $method The new method's name.
     * @param callable $closure The method's body to be executed.
     * @return void
     */
    public function macro(string $method, callable $closure): void
    {
        $this->closures[$method] = \Closure::bind($closure, $this, get_class());
    }

    /**
     * Call additional method.
     *
     * @param string $method The method's name.
     * @param array $arguments The arguments for the method.
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if(array_key_exists($method, $this->closures)) {
            return call_user_func_array($this->closures[$method], $arguments);
        }

        throw new \BadMethodCallException('Undefined method.');
    }
}
