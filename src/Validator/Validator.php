<?php

namespace Simsoft;

use BadMethodCallException;
use Closure;
use Simsoft\Validator\Constraints\Custom;
use Simsoft\Validator\Support\Errors;
use Simsoft\Validator\Support\ValidatedInput;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

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
    /** @var array Define expected attributes */
    protected array $attributes = [];

    /** @var array User input */
    private array $input = [];

    /** @var ValidatedInput Validated attributes. */
    protected ValidatedInput $validated;

    /** @var Errors Errors object */
    protected Errors $errors;

    /** @var string|GroupSequence|array<string|GroupSequence>|null Validation group */
    protected string|GroupSequence|array|null $group = null;

    /** @var array  Closures to be called before validation */
    protected array $closures = [];

    /** @var bool Indicates if the validator should stop on the first rule failure. */
    protected bool $stopOnFirstFailure = false;

    /** @var array Extended rules. */
    protected static array $extends = [];

    /**
     * Constructor.
     *
     * @param array $rules Define validation rules.
     * @param array $attributes Define expected attributes.
     */
    public function __construct(protected array $rules = [], array $attributes = [])
    {
        if ($attributes) {
            $this->attributes = $attributes;
        }

        $this->attributes = $this->attributes
            ? $this->normalizedAttributes($this->attributes)
            : array_fill_keys(array_keys($this->rules), null);

        $this->validated = new ValidatedInput();
        $this->errors = new Errors();
    }

    /**
     * Get validation object by make rules
     *
     * @param array $input Data to be validated.
     * @param array $rules Define validation rules.
     * @param array $attributes Define expected attributes.
     * @return static
     */
    public static function make(array $input, array $rules = [], array $attributes = []): static
    {
        $validator = new static($rules, $attributes);
        $validator->setData($input);
        return $validator;
    }

    /**
     * Normalized predefined attributes.a
     *
     * @param array $attributes
     * @return array
     */
    protected function normalizedAttributes(array $attributes): array
    {
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if (is_integer($key)) {
                $attrs[$value] = null;
            } else {
                $attrs[$key] = $value;
            }
        }
        return $attrs;
    }

    /**
     * The validator should stop validating subsequence attributes once a single validation failure has occurred
     *
     * @return $this
     */
    public function stopOnFirstFailure(): static
    {
        $this->stopOnFirstFailure = true;
        return $this;
    }

    /**
     * Get All the errors.
     *
     * @return Errors The errors.
     */
    public function errors(): Errors
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

        if ($this->rules === []) {
            $this->rules = $this->rules();
        }

        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(
                new ConstraintValidatorFactory([
                    EmailValidator::class => new EmailValidator(Email::VALIDATION_MODE_HTML5)
                ]))->getValidator();

        foreach($this->rules as $attribute => $rules) {
            $violations = $validator->validate($this->input[$attribute], $rules, $this->group);
            if (count($violations) > 0) {
                $this->errors->add($attribute, $violations->get(0)->getMessage());
                if ($this->stopOnFirstFailure) {
                    break;
                }
            } else {
                $this->validated->add($attribute, $this->input[$attribute]);
            }
        }

        return $this->errors()->isEmpty();
    }

    /**
     * Check the input is valid.
     *
     * @param string|GroupSequence|array|null $group The validation groups to validate. If none is given, "Default" is assumed
     * @return bool TRUE if the input is valid, FALSE otherwise.
     */
    public function passes(string|GroupSequence|array|null $group = null): bool
    {
        return $this->validated->isEmpty()
            ? $this->validate($group)
            : $this->errors()->isEmpty();
    }

    /**
     * Check the input is invalid.
     *
     * @param string|GroupSequence|array|null $group The validation groups to validate. If none is given, "Default" is assumed
     * @return bool TRUE if the input is invalid, FALSE otherwise.
     */
    public function fails(string|GroupSequence|array|null $group = null): bool
    {
        return $this->validated->isEmpty()
            ? !$this->validate($group)
            : !$this->errors()->isEmpty();
    }

    /**
     * Set input data
     *
     * @param array $input The input to be validated.
     */
    final public function setData(array $input): static
    {
        foreach($this->attributes as $attribute => $defaultValue) {
            $this->input[$attribute] = array_key_exists($attribute, $input) ? $input[$attribute]: $defaultValue;
        }

        return $this;
    }

    /**
     * Get all the unvalidated input values.
     *
     * If an attribute is provided, it will return the value of that attribute only.
     *
     * @return array
     */
    final public function all(): array
    {
        return $this->input;
    }

    /**
     * Retrieved validated inputs. If attribute is provided, return the attribute value only.
     *
     * @param string|null $attribute Input value to be returned.
     * @return mixed
     */
    final public function validated(?string $attribute = null): mixed
    {
        return $attribute
            ? $this->validated->get($attribute)
            : $this->validated->all();
    }

    /**
     * Get validated input.
     *
     * @return ValidatedInput
     */
    public function safe(): ValidatedInput
    {
        return $this->validated;
    }

    /**
     * Extends validator with named rule.
     *
     * @param string $ruleName The name of the new rule.
     * @param Closure $callable The closure which perform the validation.
     * @return void
     */
    public static function extend(string $ruleName, Closure $callable): void
    {
        static::$extends[$ruleName] = new Custom($callable);
    }

    /**
     * Add constraints rules to an attribute
     *
     * @param string $attribute The attribute name.
     * @param array|Constraint $rules The array of constraints
     * @return $this
     */
    public function addRule(string $attribute, array|Constraint $rules): static
    {
        if ($this->rules === []) {
            $this->rules = $this->rules();  // possible custom rules.
        }

        if (array_key_exists($attribute, $this->rules)) {
            if (is_array($this->rules[$attribute])) {
                if (is_array($rules)) {
                    $this->rules[$attribute] = array_merge($this->rules[$attribute], $rules);
                } elseif ($rules instanceof Constraint) {
                    $this->rules[$attribute][] = $rules;
                }
            } elseif ($this->rules[$attribute] instanceof Sequentially) {
                if (is_array($rules)) {
                    $this->rules[$attribute] = [$this->rules[$attribute], ...$rules];
                } elseif ($rules instanceof Sequentially) {
                    $this->rules[$attribute] = new Sequentially([
                        $this->rules[$attribute]->getNestedConstraints(),
                        ...$rules->getNestedConstraints()
                    ]);
                } elseif ($rules instanceof Constraint) {
                    $this->rules[$attribute] = [$this->rules[$attribute], $rules];
                }
            }elseif ($this->rules[$attribute] instanceof Constraint) {
                if (is_array($rules)) {
                    array_unshift($rules, $this->rules[$attribute]);
                } elseif ($rules instanceof Constraint) {
                    $this->rules[$attribute] = [$this->rules[$attribute], $rules];
                }
            }
        } else {
            $this->rules[$attribute] = $rules;
        }

        if (!array_key_exists($attribute, $this->attributes)) {
            $this->attributes[$attribute] = null;
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
        return $this->rules;
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
        $this->closures[$method] = Closure::bind($closure, $this, get_class());
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

        throw new BadMethodCallException('Undefined method.');
    }
}
