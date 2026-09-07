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
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validator class
 *
 * Validates user input using Symfony Validator constraints with a Laravel-inspired API.
 *
 * Subclasses customise behaviour by overriding rules() and messages(); the
 * constructor signature is expected to stay compatible so that make() can
 * instantiate them.
 *
 * Instances are request-scoped and are NOT safe to share. A Validator holds the
 * input, the validated data, and the errors of whatever it last validated, and
 * after() hooks and sometimes() rules accumulate on each call rather than
 * replacing the previous ones. Create one per validation — do not register a
 * Validator as a container singleton, and in a long-running runtime (Swoole,
 * RoadRunner, FrankenPHP) do not hold one across requests, where reuse would
 * leak one user's data into another's response.
 *
 * @phpstan-consistent-constructor
 *
 * @phpstan-type RuleSet array<string, Constraint|array<Constraint>>
 */
class Validator
{
    /**
     * Expected attributes and their default values.
     *
     * Subclasses may declare this as a list of names (['email', 'password']),
     * as name => default pairs, or a mix. The constructor normalizes it to
     * name => default before any other method reads it.
     *
     * @var array<int|string, mixed>
     */
    protected array $attributes = [];

    /** @var array<string, mixed> User input data, filtered to the expected attributes */
    private array $input = [];

    /** @var array<string, mixed> The unfiltered input as supplied to setData() */
    private array $rawInput = [];

    /** @var bool Whether attributes were explicitly declared by the caller or a subclass */
    private bool $attributesExplicit = false;

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

    /** @var array<array{attribute: string, rules: array<Constraint>|Constraint, condition: Closure}> Conditional rules */
    protected array $sometimesRules = [];

    /** @var bool Whether validate() has run since the last state change */
    private bool $hasValidated = false;

    /** @var ValidatorInterface|null Cached Symfony validator instance */
    private ?ValidatorInterface $symfonyValidator = null;

    /**
     * Constructor.
     *
     * @param RuleSet $rules Validation rules.
     * @param array<int|string, mixed> $attributes Expected attributes.
     */
    public function __construct(protected array $rules = [], array $attributes = [])
    {
        // A subclass may declare $attributes; a caller may pass them in. Either
        // form may be a list of names, so normalize before it reaches the
        // string-keyed property.
        $declared = $attributes ?: $this->attributes;

        $this->attributesExplicit = $declared !== [];

        $this->attributes = $this->attributesExplicit
            ? $this->normalizedAttributes($declared)
            : $this->attributesFromRules($this->rules);

        $this->validated = new ValidatedInput();
        $this->errors = new Errors();
    }

    /**
     * Create a validator instance with input and rules.
     *
     * @param array<string, mixed> $input Data to be validated.
     * @param RuleSet $rules Validation rules.
     * @param array<int|string, mixed> $attributes Expected attributes.
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
     * @param array<int|string, mixed> $attributes Raw attribute definitions.
     * @return array<string, mixed> Normalized attributes.
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
     * Derive expected attributes from the root segment of each rule key.
     *
     * A rule key such as 'items.*.name' depends on the 'items' entry of the
     * input, so 'items' is the attribute that must be retained.
     *
     * @param RuleSet $rules Validation rules.
     * @return array<string, null> Attributes keyed by name with null defaults.
     */
    private function attributesFromRules(array $rules): array
    {
        return array_fill_keys(
            array_map($this->attributeRoot(...), array_keys($rules)),
            null
        );
    }

    /**
     * Get the root input key a (possibly dot-notated) rule key depends on.
     *
     * @param string $key The rule key, e.g. 'items.*.name'.
     * @return string The root key, e.g. 'items'.
     */
    private function attributeRoot(string $key): string
    {
        $position = strpos($key, '.');

        return $position === false ? $key : substr($key, 0, $position);
    }

    /**
     * Ensure every rule key resolves to a retained attribute.
     *
     * When attributes were derived automatically, missing roots are added. When
     * attributes were declared explicitly, a rule referencing an undeclared
     * attribute is a configuration error: the rule would silently validate null
     * and most constraints accept null, so validation would pass on data it
     * never inspected. Fail loudly instead.
     *
     * @param RuleSet $rules Rules whose keys must be covered.
     * @return void
     * @throws InvalidArgumentException When a rule references an undeclared attribute.
     */
    private function synchronizeAttributes(array $rules): void
    {
        $added = false;

        foreach (array_keys($rules) as $key) {
            $root = $this->attributeRoot($key);

            if (array_key_exists($root, $this->attributes)) {
                continue;
            }

            if ($this->attributesExplicit) {
                throw new InvalidArgumentException(sprintf(
                    'The rule "%s" refers to the attribute "%s", which is not declared in the '
                    . 'expected attributes [%s]. Add it to the attribute list, or remove the rule. '
                    . 'Validating an undeclared attribute would silently pass, because its value '
                    . 'is always null.',
                    $key,
                    $root,
                    implode(', ', array_keys($this->attributes))
                ));
            }

            $this->attributes[$root] = null;
            $added = true;
        }

        if ($added) {
            $this->synchronizeInput();
        }
    }

    /**
     * Rebuild the filtered input from the raw input and current attributes.
     *
     * @return void
     */
    private function synchronizeInput(): void
    {
        $input = [];

        foreach ($this->attributes as $attribute => $defaultValue) {
            // PHP narrows numeric-string array keys to int, so an attribute
            // named "0" arrives here as an int. Names are strings by contract.
            $name = (string)$attribute;

            $input[$name] = array_key_exists($name, $this->rawInput)
                ? $this->rawInput[$name]
                : $defaultValue;
        }

        $this->input = $input;
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
     * Get the errors' collection.
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
     * Hooks accumulate: calling this twice registers two hooks, and both run on
     * every subsequent validate(). Register them once per instance rather than
     * on a reused one.
     *
     * @param Closure $callback Callback receiving this Validator instance.
     * @return static
     */
    public function after(Closure $callback): static
    {
        $this->afterHooks[] = $callback;
        $this->hasValidated = false;
        return $this;
    }

    /**
     * Conditionally apply rules to an attribute.
     *
     * The rules are only applied when the condition closure returns true.
     * The condition receives the full input array.
     *
     * Like after(), registrations accumulate across calls and are re-evaluated
     * on every subsequent validate().
     *
     * @param string $attribute The attribute name (supports dot notation).
     * @param array<Constraint>|Constraint $rules Constraints to apply.
     * @param Closure $condition Closure receiving an input array, returns bool.
     * @return static
     */
    public function sometimes(string $attribute, array|Constraint $rules, Closure $condition): static
    {
        $this->sometimesRules[] = [
            'attribute' => $attribute,
            'rules' => $rules,
            'condition' => $condition,
        ];

        $this->hasValidated = false;

        return $this;
    }

    /**
     * Validate the input against the rules.
     *
     * @param string|GroupSequence|array<string|GroupSequence>|null $group Validation groups to apply.
     * @return bool TRUE if valid, FALSE otherwise.
     * @throws InvalidArgumentException When a rule references an undeclared attribute.
     */
    final public function validate(string|GroupSequence|array|null $group = null): bool
    {
        $this->group = $group;
        $this->errors->reset();
        $this->validated->reset();

        if ($this->rules === []) {
            $this->rules = $this->rules();
        }

        // Resolve the base rules first so conditional closures see the real input.
        $this->synchronizeAttributes($this->rules);

        $rules = $this->resolveRules();
        $this->synchronizeAttributes($rules);

        [$expandedRules, $ruleSources] = $this->expandRules($rules);

        $validator = $this->getSymfonyValidator();
        $messages = $this->messages();

        foreach ($expandedRules as $attribute => $constraints) {
            $value = $this->getValue($attribute);
            $violations = $validator->validate($value, $constraints, $this->group);
            $violationCount = count($violations);

            if ($violationCount > 0) {
                // A custom message describes the attribute as a whole, so it is
                // recorded once rather than repeated for every violation.
                $customMessage = $messages[$attribute] ?? $messages[$ruleSources[$attribute]] ?? null;

                if ($customMessage !== null) {
                    $this->errors->add($attribute, $customMessage);
                } else {
                    for ($index = 0; $index < $violationCount; $index++) {
                        $this->errors->add($attribute, $violations->get($index)->getMessage());
                    }
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

        $this->hasValidated = true;

        return $this->errors()->isEmpty();
    }

    /**
     * Check if the input passes validation.
     *
     * @param string|GroupSequence|array<string|GroupSequence>|null $group Validation groups to apply.
     * @return bool TRUE if valid, FALSE otherwise.
     */
    public function passes(string|GroupSequence|array|null $group = null): bool
    {
        return $this->hasValidated
            ? $this->errors()->isEmpty()
            : $this->validate($group);
    }

    /**
     * Check if the input fails validation.
     *
     * @param string|GroupSequence|array<string|GroupSequence>|null $group Validation groups to apply.
     * @return bool TRUE if invalid, FALSE otherwise.
     */
    public function fails(string|GroupSequence|array|null $group = null): bool
    {
        return !$this->passes($group);
    }

    /**
     * Set the input data to validate.
     *
     * @param array<string, mixed> $input The input data.
     * @return static
     */
    final public function setData(array $input): static
    {
        $this->rawInput = $input;
        $this->synchronizeInput();

        $this->validated->reset();
        $this->errors->reset();
        $this->hasValidated = false;

        return $this;
    }

    /**
     * Get all raw input values.
     *
     * @return array<string, mixed>
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
     * @param array<Constraint>|Constraint $rules Constraints to add.
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

        $root = $this->attributeRoot($attribute);

        if (!array_key_exists($root, $this->attributes)) {
            $this->attributes[$root] = null;
            $this->synchronizeInput();
        }

        $this->hasValidated = false;

        return $this;
    }

    /**
     * Define the validation rules (override in subclasses).
     *
     * @return RuleSet The validation rules.
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
     * @param array<int, mixed> $arguments The method arguments.
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
     * @param mixed|null $default Default value if the key is not found.
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->input)) {
            return $this->input[$key];
        }

        $segments = explode('.', $key);
        $value = $this->input;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
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
     * @param RuleSet $rules The rules to expand.
     * @return array{0: RuleSet, 1: array<string, string>} Expanded rules, and a map
     *               of each expanded key back to the rule key it came from.
     */
    private function expandRules(array $rules): array
    {
        $expanded = [];
        $sources = [];

        foreach ($rules as $attribute => $constraint) {
            if (!str_contains($attribute, '*')) {
                $expanded[$attribute] = $constraint;
                $sources[$attribute] = $attribute;
                continue;
            }

            foreach ($this->expandWildcardKey($attribute) as $expandedKey) {
                $expanded[$expandedKey] = $constraint;
                $sources[$expandedKey] = $attribute;
            }
        }

        return [$expanded, $sources];
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
     * Build the effective rule set for this run.
     *
     * Conditional rules are merged into a copy so that a condition which was
     * true for one input does not leak into a later run with different input.
     *
     * @return RuleSet The rules to validate against.
     */
    private function resolveRules(): array
    {
        $rules = $this->rules;

        foreach ($this->sometimesRules as $entry) {
            if (($entry['condition'])($this->input)) {
                $rules[$entry['attribute']] = $entry['rules'];
            }
        }

        return $rules;
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
