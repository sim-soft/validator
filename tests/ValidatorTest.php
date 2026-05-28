<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Simsoft\Validator\Constraints\Custom;
use Simsoft\Validator\Rule;
use Simsoft\Validator\Support\Errors;
use Simsoft\Validator\Support\ValidatedInput;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

/**
 * ValidatorTest class
 *
 * Comprehensive unit tests for the main Validator class.
 */
class ValidatorTest extends TestCase
{
    // ─── make() ───────────────────────────────────────────────────────

    #[Test]
    public function makeCreatesValidatorWithInputAndRules(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $this->assertInstanceOf(Validator::class, $validator);
        $this->assertSame(['name' => 'Alice'], $validator->all());
    }

    #[Test]
    public function makeWithEmptyInputUsesNullDefaults(): void
    {
        $validator = Validator::make(
            [],
            ['name' => new NotBlank()]
        );

        $this->assertSame(['name' => null], $validator->all());
    }

    #[Test]
    public function makeWithAttributesDefinesExpectedFields(): void
    {
        $validator = Validator::make(
            ['email' => 'test@test.com'],
            ['email' => new NotBlank()],
            ['email', 'name' => 'default']
        );

        $this->assertSame(['email' => 'test@test.com', 'name' => 'default'], $validator->all());
    }

    // ─── validate() ──────────────────────────────────────────────────

    #[Test]
    public function validateReturnsTrueWhenAllRulesPass(): void
    {
        $validator = Validator::make(
            ['email' => 'user@example.com'],
            ['email' => new Email()]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function validateReturnsFalseWhenRuleFails(): void
    {
        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => new Email(message: 'Bad email')]
        );

        $this->assertFalse($validator->validate());
    }

    #[Test]
    public function validateCollectsErrorMessages(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => new NotBlank(message: 'Required')]
        );

        $validator->validate();

        $this->assertSame('Required', $validator->errors()->first('email'));
    }

    #[Test]
    public function validateWithMultipleAttributes(): void
    {
        $validator = Validator::make(
            ['email' => '', 'name' => ''],
            [
                'email' => new NotBlank(message: 'Email required'),
                'name' => new NotBlank(message: 'Name required'),
            ]
        );

        $validator->validate();

        $this->assertSame('Email required', $validator->errors()->first('email'));
        $this->assertSame('Name required', $validator->errors()->first('name'));
    }

    // ─── passes() / fails() ─────────────────────────────────────────

    #[Test]
    public function passesReturnsTrueOnValidInput(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function passesReturnsFalseOnInvalidInput(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => new NotBlank()]
        );

        $this->assertFalse($validator->passes());
    }

    #[Test]
    public function failsReturnsTrueOnInvalidInput(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => new NotBlank()]
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function failsReturnsFalseOnValidInput(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function passesDoesNotRevalidateOnSecondCall(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $this->assertTrue($validator->passes());
        $this->assertTrue($validator->passes());
    }

    // ─── stopOnFirstFailure() ────────────────────────────────────────

    #[Test]
    public function stopOnFirstFailureStopsAfterFirstError(): void
    {
        $validator = Validator::make(
            ['email' => '', 'name' => ''],
            [
                'email' => new NotBlank(message: 'Email required'),
                'name' => new NotBlank(message: 'Name required'),
            ]
        );

        $validator->stopOnFirstFailure();
        $validator->validate();

        $this->assertTrue($validator->errors()->has('email'));
        $this->assertFalse($validator->errors()->has('name'));
    }

    // ─── validated() ─────────────────────────────────────────────────

    #[Test]
    public function validatedReturnsAllValidatedData(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice', 'age' => '30'],
            [
                'name' => new NotBlank(),
                'age' => new NotBlank(),
            ]
        );

        $validator->validate();

        $this->assertSame(['name' => 'Alice', 'age' => '30'], $validator->validated());
    }

    #[Test]
    public function validatedReturnsSingleAttributeValue(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $validator->validate();

        $this->assertSame('Alice', $validator->validated('name'));
    }

    #[Test]
    public function validatedReturnsNullForFailedAttribute(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => new NotBlank()]
        );

        $validator->validate();

        $this->assertNull($validator->validated('name'));
    }

    #[Test]
    public function validatedExcludesFailedAttributes(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice', 'email' => ''],
            [
                'name' => new NotBlank(),
                'email' => new NotBlank(),
            ]
        );

        $validator->validate();

        $this->assertSame(['name' => 'Alice'], $validator->validated());
    }

    // ─── safe() ──────────────────────────────────────────────────────

    #[Test]
    public function safeReturnsValidatedInputInstance(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $validator->validate();

        $this->assertInstanceOf(ValidatedInput::class, $validator->safe());
    }

    #[Test]
    public function safeOnlyReturnsSubset(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice', 'age' => '30'],
            [
                'name' => new NotBlank(),
                'age' => new NotBlank(),
            ]
        );

        $validator->validate();

        $this->assertSame(['name' => 'Alice'], $validator->safe()->only(['name']));
    }

    #[Test]
    public function safeExceptExcludesKeys(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice', 'age' => '30'],
            [
                'name' => new NotBlank(),
                'age' => new NotBlank(),
            ]
        );

        $validator->validate();

        $this->assertSame(['age' => '30'], $validator->safe()->except(['name']));
    }

    // ─── errors() ────────────────────────────────────────────────────

    #[Test]
    public function errorsReturnsErrorsInstance(): void
    {
        $validator = Validator::make([], []);
        $this->assertInstanceOf(Errors::class, $validator->errors());
    }

    #[Test]
    public function errorsIsEmptyBeforeValidation(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => new NotBlank()]);
        $this->assertTrue($validator->errors()->isEmpty());
    }

    // ─── setData() / all() ───────────────────────────────────────────

    #[Test]
    public function setDataPopulatesInput(): void
    {
        $validator = new Validator(
            ['name' => new NotBlank()],
            ['name']
        );

        $validator->setData(['name' => 'Bob']);

        $this->assertSame(['name' => 'Bob'], $validator->all());
    }

    #[Test]
    public function setDataUsesDefaultForMissingAttributes(): void
    {
        $validator = new Validator(
            ['name' => new NotBlank()],
            ['name' => 'default_name']
        );

        $validator->setData([]);

        $this->assertSame(['name' => 'default_name'], $validator->all());
    }

    #[Test]
    public function setDataIgnoresExtraInputKeys(): void
    {
        $validator = new Validator(
            ['name' => new NotBlank()],
            ['name']
        );

        $validator->setData(['name' => 'Alice', 'extra' => 'ignored']);

        $this->assertSame(['name' => 'Alice'], $validator->all());
    }

    #[Test]
    public function setDataResetsValidationState(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $validator->validate();
        $this->assertTrue($validator->passes());

        $validator->setData(['name' => '']);
        $this->assertFalse($validator->passes());
    }

    // ─── addRule() ───────────────────────────────────────────────────

    #[Test]
    public function addRuleAddsNewAttribute(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice', 'email' => ''],
            ['name' => new NotBlank()],
            ['name', 'email']
        );

        $validator->addRule('email', new NotBlank(message: 'Email required'));
        $validator->validate();

        $this->assertSame('Email required', $validator->errors()->first('email'));
    }

    #[Test]
    public function addRuleAppendsToExistingArrayRules(): void
    {
        $validator = Validator::make(
            ['name' => 'AB'],
            ['name' => [new NotBlank()]]
        );

        $validator->addRule('name', new Length(min: 5, minMessage: 'Too short'));
        $validator->validate();

        $this->assertSame('Too short', $validator->errors()->first('name'));
    }

    #[Test]
    public function addRuleAppendsConstraintToExistingArray(): void
    {
        $validator = Validator::make(
            ['name' => 'AB'],
            ['name' => [new NotBlank()]]
        );

        $validator->addRule('name', new Length(min: 5, minMessage: 'Min 5'));
        $validator->validate();

        $this->assertSame('Min 5', $validator->errors()->first('name'));
    }

    // ─── macro() / __call() ──────────────────────────────────────────

    #[Test]
    public function macroAddsCustomMethod(): void
    {
        $validator = Validator::make(['name' => 'Alice'], ['name' => new NotBlank()]);

        $validator->macro('getInput', function () {
            return $this->all();
        });

        $this->assertSame(['name' => 'Alice'], $validator->getInput());
    }

    #[Test]
    public function callThrowsForUndefinedMethod(): void
    {
        $validator = Validator::make([], []);

        $this->expectException(BadMethodCallException::class);
        $validator->nonExistentMethod();
    }

    // ─── Sequentially constraint ─────────────────────────────────────

    #[Test]
    public function sequentiallyStopsAtFirstFailure(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => new Sequentially([
                new NotBlank(message: 'Required'),
                new Email(message: 'Invalid email'),
            ])]
        );

        $validator->validate();

        $this->assertSame('Required', $validator->errors()->first('email'));
    }

    // ─── Edge cases ──────────────────────────────────────────────────

    #[Test]
    public function validateWithEmptyRulesPassesImmediately(): void
    {
        $validator = Validator::make(['name' => 'Alice'], []);

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function validateWithNullInputValue(): void
    {
        $validator = Validator::make(
            ['field' => null],
            ['field' => new Custom(function (mixed $value, Closure $fail): void {
                if ($value === null) {
                    $fail('Cannot be null');
                }
            })]
        );

        $this->assertFalse($validator->validate());
    }

    #[Test]
    public function validateCanBeCalledMultipleTimesWithoutDuplication(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => new NotBlank(message: 'Required')]
        );

        $validator->validate();
        $validator->validate();

        $this->assertSame(['name' => ['Required']], $validator->errors()->all());
    }

    #[Test]
    public function validateResetsValidatedDataOnRerun(): void
    {
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $validator->validate();
        $this->assertSame(['name' => 'Alice'], $validator->validated());

        $validator->validate();
        $this->assertSame(['name' => 'Alice'], $validator->validated());
    }

    // ─── after() ─────────────────────────────────────────────────────

    #[Test]
    public function afterHookRunsAfterValidation(): void
    {
        $hookRan = false;
        $validator = Validator::make(
            ['name' => 'Alice'],
            ['name' => new NotBlank()]
        );

        $validator->after(function (Validator $val) use (&$hookRan) {
            $hookRan = true;
        });

        $validator->validate();
        $this->assertTrue($hookRan);
    }

    #[Test]
    public function afterHookCanAddErrors(): void
    {
        $validator = Validator::make(
            ['password' => 'secret', 'password_confirm' => 'different'],
            [
                'password' => new NotBlank(),
                'password_confirm' => new NotBlank(),
            ]
        );

        $validator->after(function (Validator $val) {
            if ($val->validated('password') !== $val->validated('password_confirm')) {
                $val->errors()->add('password_confirm', 'Passwords do not match');
            }
        });

        $this->assertFalse($validator->validate());
        $this->assertSame('Passwords do not match', $validator->errors()->first('password_confirm'));
    }

    #[Test]
    public function afterHookDoesNotRunBeforeRules(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => new NotBlank(message: 'Required')]
        );

        $validator->after(function (Validator $val) {
            $val->errors()->add('extra', 'After hook error');
        });

        $validator->validate();

        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('extra'));
    }

    // ─── sometimes() instance method ─────────────────────────────────

    #[Test]
    public function sometimesAppliesRulesWhenConditionIsTrue(): void
    {
        $validator = Validator::make(
            ['role' => 'admin', 'permissions' => ''],
            ['role' => new NotBlank()],
            ['role', 'permissions']
        );

        $validator->sometimes('permissions', new NotBlank(message: 'Permissions required'), function (array $input) {
            return $input['role'] === 'admin';
        });

        $this->assertFalse($validator->validate());
        $this->assertSame('Permissions required', $validator->errors()->first('permissions'));
    }

    #[Test]
    public function sometimesSkipsRulesWhenConditionIsFalse(): void
    {
        $validator = Validator::make(
            ['role' => 'user', 'permissions' => ''],
            ['role' => new NotBlank()],
            ['role', 'permissions']
        );

        $validator->sometimes('permissions', new NotBlank(message: 'Permissions required'), function (array $input) {
            return $input['role'] === 'admin';
        });

        $this->assertTrue($validator->validate());
    }

    // ─── Nested dot notation ─────────────────────────────────────────

    #[Test]
    public function validateSupportsNestedDotNotation(): void
    {
        $validator = Validator::make(
            ['address' => ['city' => '']],
            ['address.city' => new NotBlank(message: 'City is required')],
            ['address']
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('City is required', $validator->errors()->first('address.city'));
    }

    #[Test]
    public function validateNestedDotNotationPasses(): void
    {
        $validator = Validator::make(
            ['address' => ['city' => 'London']],
            ['address.city' => new NotBlank(message: 'City is required')],
            ['address']
        );

        $this->assertTrue($validator->validate());
        $this->assertSame('London', $validator->validated('address.city'));
    }

    #[Test]
    public function validateDeepNestedDotNotation(): void
    {
        $validator = Validator::make(
            ['user' => ['profile' => ['bio' => '']]],
            ['user.profile.bio' => new NotBlank(message: 'Bio required')],
            ['user']
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Bio required', $validator->errors()->first('user.profile.bio'));
    }

    #[Test]
    public function validateNestedReturnsNullForMissingPath(): void
    {
        $validator = Validator::make(
            ['address' => []],
            ['address.city' => new Custom(function (mixed $value, Closure $fail) {
                if ($value === null) {
                    $fail('Missing');
                }
            })],
            ['address']
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Missing', $validator->errors()->first('address.city'));
    }

    // ─── Wildcard rules ──────────────────────────────────────────────

    #[Test]
    public function validateExpandsWildcardRules(): void
    {
        $validator = Validator::make(
            ['items' => [['name' => ''], ['name' => 'Valid']]],
            ['items.*.name' => new NotBlank(message: 'Item name required')],
            ['items']
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Item name required', $validator->errors()->first('items.0.name'));
        $this->assertFalse($validator->errors()->has('items.1.name'));
    }

    #[Test]
    public function validateWildcardAllPass(): void
    {
        $validator = Validator::make(
            ['items' => [['name' => 'A'], ['name' => 'B']]],
            ['items.*.name' => new NotBlank(message: 'Item name required')],
            ['items']
        );

        $this->assertTrue($validator->validate());
        $this->assertSame('A', $validator->validated('items.0.name'));
        $this->assertSame('B', $validator->validated('items.1.name'));
    }

    #[Test]
    public function validateWildcardWithEmptyArray(): void
    {
        $validator = Validator::make(
            ['items' => []],
            ['items.*.name' => new NotBlank(message: 'Item name required')],
            ['items']
        );

        $this->assertTrue($validator->validate());
    }

    // ─── messages() override ─────────────────────────────────────────

    #[Test]
    public function messagesOverrideReplacesConstraintMessage(): void
    {
        $validator = new class (['name' => new NotBlank()], ['name']) extends Validator {
            protected function messages(): array
            {
                return ['name' => 'Custom name error'];
            }
        };

        $validator->setData(['name' => '']);
        $validator->validate();

        $this->assertSame('Custom name error', $validator->errors()->first('name'));
    }

    // ─── All violations collected ────────────────────────────────────

    #[Test]
    public function validateCollectsAllViolationsPerAttribute(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => [
                new NotBlank(message: 'Cannot be blank'),
                new Length(min: 3, minMessage: 'Too short'),
            ]]
        );

        $validator->validate();

        $messages = iterator_to_array($validator->errors()->get('name'));
        $this->assertContains('Cannot be blank', $messages);
    }

    // ─── Edge cases ──────────────────────────────────────────────────

    #[Test]
    public function allReturnsEmptyWhenNoAttributesDefined(): void
    {
        $validator = Validator::make([], []);
        $this->assertSame([], $validator->all());
    }
}
