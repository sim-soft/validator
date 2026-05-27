<?php

namespace Simsoft;

use BadMethodCallException;
use Closure;
use Simsoft\Validator\Support\Errors;
use Simsoft\Validator\Support\ValidatedInput;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validator class
 *
 * Validates user input using Symfony Validator constraints with a Laravel-inspired API.
 */
class Validator
{
    /** @var array Expected attributes and their default values */
    protected array $attributes = [];

    /** @var array User input data */
    private array $input = [];

    /** @var ValidatedInput Validated attributes */
    protected ValidatedInput $validated;

    /** @var Errors Error messages */
    protected Errors $errors;

    /** @var string|GroupSequence|array<string|GroupSequence>|null Validation group */
    protected string|GroupSequence|array|null $group = null;

    /** @var array<string, Closure> Macro closures */
    protected array $closures = [];

    /** @var bool Stop on first attribute failure */
    protected bool $stopOnFirstFailure = false;

    /** @var array<Closure> After-validation hooks */
    protected array $afterHooks = [];

    /** @var array<array{attribute: string, rules: array|Constraint, condition: Closure}> Conditional rules */
    protected array $sometimesRules = [];

    /** @var ValidatorInterface|null Cached Symfony validator instance */
    private ?ValidatorInterface $symfonyValidator = null;

    /**
     * Constructor.
     *
     * @param array $rules Validation rules.
     * @param array $attributes Expected attributes.
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
     * Create a validator instance with input and rules.
     *
     * @param array $input Data to be validated.
     * @param array $rules Validation rules.
     * @param array $attributes Expected attributes.
     * @return static
     */
    public static function make(array $input, array $rules = [], array $attributes = []): static
    {
        $validator = new static($rules, $attributes);
        $validator->setData($input);
        return $validator;
    }

    /**
     * Normalize attribute definitions into key => default pairs.
     *
     * @param array $attributes Raw attribute definitions.
     * @return array Normalized attributes.
     */
    protected function normalizedAttributes(array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $key => $value) {
            if (is_integer($key)) {
                $normalized[$value] = null;
            } else {
                $normalized[$key] = $value;
            }
        }
        return $normalized;
    }

    /**
     * Stop validating remaining attributes after the first failure.
     *
     * @return static
     */
    public function stopOnFirstFailure(): static
    {
        $this->stopOnFirstFailure = true;
        return $this;
    }

    /**
     * Get the errors collection.
     *
     * @return Errors
     */
    public function errors(): Errors
    {
        return $this->errors;
    }

    /**
     * Register a callback to run after validation.
     *
     * @param Closure $callback Callback receiving this Validator instance.
     * @return static
     */
    public function after(Closure $callback): static
    {
        $this->afterHooks[] = $callback;
        return $this;
    }

    /**
     * Conditionally apply rules to an attribute.
     *
     * The rules are only applied when the condition closure returns true.
     * The condition receives the full input array.
     *
     * @param string $attribute The attribute name (supports dot notation).
     * @param array|Constraint $rules Constraints to apply.
     * @param Closure $condition Closure receiving input array, returns bool.
     * @return static
     */
    public function sometimes(string $attribute, array|Constraint $rules, Closure $condition): static
    {
        $this->sometimesRules[] = [
            'attribute' => $attribute,
            'rules' => $rules,
            'condition' => $condition,
        ];
        return $this;
    }

    /**
     * Validate the input against the rules.
     *
     * @param string|GroupSequence|array|null $group Validation groups to apply.
     * @return bool TRUE if valid, FALSE otherwise.
     */
    final public function validate(string|GroupSequence|array|null $group = null): bool
    {
        $this->group = $group;
        $this->errors->reset();
        $this->validated->reset();

        if ($this->rules === []) {
            $this->rules = $this->rules();
        }

        $this->applySometimesRules();
        $expandedRules = $this->expandRules($this->rules);

        $validator = $this->getSymfonyValidator();
        $messages = $this->messages();

        foreach ($expandedRules as $attribute => $rules) {
            $value = $this->getValue($attribute);
            $violations = $validator->validate($value, $rules, $this->group);
            $violationCount = count($violations);

            if ($violationCount > 0) {
                for ($index = 0; $index < $violationCount; $index++) {
                    $violationMessage = $violations->get($index)->getMessage();
                    $message = $messages[$attribute] ?? $violationMessage;
                    $this->errors->add($attribute, $message);
                }
                if ($this->stopOnFirstFailure) {
                    break;
                }
            } else {
                $this->validated->add($attribute, $value);
            }
        }

        foreach ($this->afterHooks as $hook) {
            $hook($this);
        }

        return $this->errors()->isEmpty();
    }

    /**
     * Check if the input passes validation.
     *
     * @param string|GroupSequence|array|null $group Validation groups to apply.
     * @return bool TRUE if valid, FALSE otherwise.
     */
    public function passes(string|GroupSequence|array|null $group = null): bool
    {
        return $this->validated->isEmpty()
            ? $this->validate($group)
            : $this->errors()->isEmpty();
    }

    /**
     * Check if the input fails validation.
     *
     * @param string|GroupSequence|array|null $group Validation groups to apply.
     * @return bool TRUE if invalid, FALSE otherwise.
     */
    public function fails(string|GroupSequence|array|null $group = null): bool
    {
        return $this->validated->isEmpty()
            ? !$this->validate($group)
            : !$this->errors()->isEmpty();
    }

    /**
     * Set the input data to validate.
     *
     * @param array $input The input data.
     * @return static
     */
    final public function setData(array $input): static
    {
        foreach ($this->attributes as $attribute => $defaultValue) {
            $this->input[$attribute] = array_key_exists($attribute, $input) ? $input[$attribute] : $defaultValue;
        }

        $this->validated->reset();
        $this->errors->reset();

        return $this;
    }

    /**
     * Get all raw input values.
     *
     * @return array
     */
    final public function all(): array
    {
        return $this->input;
    }

    /**
     * Get validated data, optionally for a single attribute.
     *
     * @param string|null $attribute Attribute name, or null for all.
     * @return mixed
     */
    final public function validated(?string $attribute = null): mixed
    {
        return $attribute
            ? $this->validated->get($attribute)
            : $this->validated->all();
    }

    /**
     * Get the validated input object for subset operations.
     *
     * @return ValidatedInput
     */
    public function safe(): ValidatedInput
    {
        return $this->validated;
    }

    /**
     * Add constraint rules to an attribute.
     *
     * @param string $attribute The attribute name.
     * @param array|Constraint $rules Constraints to add.
     * @return static
     */
    public function addRule(string $attribute, array|Constraint $rules): static
    {
        if ($this->rules === []) {
            $this->rules = $this->rules();
        }

        if (array_key_exists($attribute, $this->rules)) {
            if (is_array($this->rules[$attribute])) {
                if (is_array($rules)) {
                    $this->rules[$attribute] = [...$this->rules[$attribute], ...$rules];
                } elseif ($rules instanceof Constraint) {
                    $this->rules[$attribute][] = $rules;
                }
            } elseif ($this->rules[$attribute] instanceof Sequentially) {
                if (is_array($rules)) {
                    $this->rules[$attribute] = [$this->rules[$attribute], ...$rules];
                } elseif ($rules instanceof Sequentially) {
                    $this->rules[$attribute] = new Sequentially([
                        ...$this->rules[$attribute]->getNestedConstraints(),
                        ...$rules->getNestedConstraints(),
                    ]);
                } elseif ($rules instanceof Constraint) {
                    $this->rules[$attribute] = [$this->rules[$attribute], $rules];
                }
            } elseif ($this->rules[$attribute] instanceof Constraint) {
                if (is_array($rules)) {
                    $this->rules[$attribute] = [$this->rules[$attribute], ...$rules];
                } elseif ($rules instanceof Constraint) {
                    $this->rules[$attribute] = [$this->rules[$attribute], $rules];
                }
            }
        } else {
            $this->rules[$attribute] = $rules;
        }

        if (!array_key_exists($attribute, $this->attributes)) {
            $this->attributes[$attribute] = null;
            $this->input[$attribute] = null;
        }

        return $this;
    }

    /**
     * Define the validation rules (override in subclasses).
     *
     * @return array The validation rules.
     */
    protected function rules(): array
    {
        return $this->rules;
    }

    /**
     * Define custom error messages per attribute (override in subclasses).
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Register a macro method on this instance.
     *
     * @param string $method The method name.
     * @param Closure $closure The method body.
     * @return void
     */
    public function macro(string $method, Closure $closure): void
    {
        $this->closures[$method] = Closure::bind($closure, $this, static::class);
    }

    /**
     * Call a registered macro method.
     *
     * @param string $method The method name.
     * @param array $arguments The method arguments.
     * @return mixed
     * @throws BadMethodCallException When the method is not defined.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (array_key_exists($method, $this->closures)) {
            return ($this->closures[$method])(...$arguments);
        }

        throw new BadMethodCallException("Undefined method: $method");
    }

    /**
     * Get a value from input using dot notation.
     *
     * @param string $key The dot-notated key (e.g. 'address.city').
     * @return mixed
     */
    private function getValue(string $key): mixed
    {
        if (array_key_exists($key, $this->input)) {
            return $this->input[$key];
        }

        $segments = explode('.', $key);
        $value = $this->input;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Expand wildcard rules against actual input data.
     *
     * Rules with '*' in the key are expanded to match actual array indices.
     * For example, 'items.*.name' with input ['items' => [['name' => 'A'], ['name' => 'B']]]
     * expands to 'items.0.name' and 'items.1.name'.
     *
     * @param array $rules The rules to expand.
     * @return array Expanded rules.
     */
    private function expandRules(array $rules): array
    {
        $expanded = [];

        foreach ($rules as $attribute => $constraint) {
            if (!str_contains($attribute, '*')) {
                $expanded[$attribute] = $constraint;
                continue;
            }

            $keys = $this->expandWildcardKey($attribute);
            foreach ($keys as $expandedKey) {
                $expanded[$expandedKey] = $constraint;
            }
        }

        return $expanded;
    }

    /**
     * Expand a wildcard key into concrete keys based on input data.
     *
     * @param string $pattern The pattern with wildcards (e.g. 'items.*.name').
     * @return array<string> Expanded keys.
     */
    private function expandWildcardKey(string $pattern): array
    {
        $segments = explode('.', $pattern);
        $keys = [''];

        foreach ($segments as $segment) {
            $newKeys = [];
            foreach ($keys as $currentKey) {
                $prefix = $currentKey === '' ? '' : "$currentKey.";

                if ($segment === '*') {
                    $value = $this->getValue(rtrim($currentKey, '.'));
                    if (is_array($value)) {
                        foreach (array_keys($value) as $index) {
                            $newKeys[] = "$prefix$index";
                        }
                    }
                } else {
                    $newKeys[] = "$prefix$segment";
                }
            }
            $keys = $newKeys;
        }

        return $keys;
    }

    /**
     * Apply conditional sometimes rules to the rules array.
     *
     * @return void
     */
    private function applySometimesRules(): void
    {
        foreach ($this->sometimesRules as $entry) {
            if (($entry['condition'])($this->input)) {
                $this->rules[$entry['attribute']] = $entry['rules'];

                if (!array_key_exists($entry['attribute'], $this->attributes)) {
                    $this->attributes[$entry['attribute']] = null;
                }
            }
        }
    }

    /**
     * Get or create the cached Symfony validator instance.
     *
     * @return ValidatorInterface
     */
    private function getSymfonyValidator(): ValidatorInterface
    {
        if ($this->symfonyValidator === null) {
            $this->symfonyValidator = Validation::createValidatorBuilder()
                ->setConstraintValidatorFactory(
                    new ConstraintValidatorFactory([
                        EmailValidator::class => new EmailValidator(Email::VALIDATION_MODE_HTML5),
                    ])
                )->getValidator();
        }

        return $this->symfonyValidator;
    }
}
