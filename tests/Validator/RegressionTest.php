<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Simsoft\Validator\Constraints\ValidationRule;
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * RegressionTest class
 *
 * Guards against the fail-open and shared-state defects found in the
 * production-readiness review. Each test fails against the pre-fix code.
 */
class RegressionTest extends TestCase
{
    // ─── Fail-open: input retention ──────────────────────────────────

    #[Test]
    public function wildcardRulesWorkWithoutDeclaringAttributes(): void
    {
        // Previously the 'items' input was discarded because only the literal
        // key 'items.*.name' was retained, so the wildcard expanded to nothing
        // and validation passed on data it never inspected.
        $validator = Validator::make(
            ['items' => [['name' => 'A'], ['name' => '']]],
            ['items.*.name' => new NotBlank(message: 'Item name required')]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Item name required', $validator->errors()->first('items.1.name'));
    }

    #[Test]
    public function nestedDotRulesWorkWithoutDeclaringAttributes(): void
    {
        $validator = Validator::make(
            ['address' => ['city' => '']],
            ['address.city' => new NotBlank(message: 'City required')]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('City required', $validator->errors()->first('address.city'));
    }

    #[Test]
    public function ruleReferencingUndeclaredAttributeThrows(): void
    {
        // A typo between rule key and attribute name used to pass silently:
        // the value resolved to null, and Choice accepts null.
        $validator = Validator::make(
            ['role' => 'superuser'],
            ['rols' => new Choice(choices: ['user'])],
            ['role']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/refers to the attribute "rols"/');

        $validator->validate();
    }

    #[Test]
    public function undeclaredAttributeErrorNamesTheOffendingRule(): void
    {
        $validator = Validator::make(
            ['email' => 'a@b.com'],
            ['email' => new NotBlank(), 'password' => new Length(min: 8)],
            ['email']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"password"/');

        $validator->validate();
    }

    // ─── sometimes() must not leak across runs ───────────────────────

    #[Test]
    public function sometimesRulesDoNotPersistAcrossSetData(): void
    {
        $validator = Validator::make(
            ['role' => 'admin', 'permissions' => null],
            ['role' => new NotBlank()],
            ['role', 'permissions']
        );

        $validator->sometimes(
            'permissions',
            new NotBlank(message: 'Permissions required'),
            fn(array $input) => $input['role'] === 'admin'
        );

        $this->assertFalse($validator->validate());

        // Same instance, different input: the condition is now false, so the
        // conditional rule must no longer apply.
        $validator->setData(['role' => 'user', 'permissions' => null]);

        $this->assertTrue($validator->validate());
        $this->assertTrue($validator->errors()->isEmpty());
    }

    // ─── passes()/fails() caching ────────────────────────────────────

    #[Test]
    public function afterHookRunsOncePerValidation(): void
    {
        $runs = 0;
        $validator = Validator::make(['name' => ''], ['name' => new NotBlank()]);
        $validator->after(function () use (&$runs): void {
            $runs++;
        });

        $validator->validate();
        $validator->fails();
        $validator->passes();

        // When every field fails, validated() is empty; that used to be read as
        // "never validated", re-running the whole pass and the hooks with it.
        $this->assertSame(1, $runs);
    }

    #[Test]
    public function passesReflectsRulesAddedAfterAPassingRun(): void
    {
        $validator = Validator::make(['name' => 'Alice'], ['name' => new NotBlank()]);

        $this->assertTrue($validator->passes());

        $validator->addRule('name', new Length(min: 100, minMessage: 'Too short'));

        $this->assertFalse($validator->passes());
    }

    #[Test]
    public function failsIsAlwaysTheInverseOfPasses(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => new NotBlank()]);

        $this->assertTrue($validator->fails());
        $this->assertFalse($validator->passes());
    }

    // ─── Shared constraint state ─────────────────────────────────────

    #[Test]
    public function sharedRuleInstanceReportsPerFieldMessages(): void
    {
        $rule = new RegressionPasswordRule();

        $validator = Validator::make(
            ['p1' => 'short', 'p2' => 'abcdefghij'],
            ['p1' => $rule, 'p2' => $rule]
        );

        $validator->validate();

        $this->assertSame('Password too short.', $validator->errors()->first('p1'));
        $this->assertSame('Password needs a digit.', $validator->errors()->first('p2'));
    }

    #[Test]
    public function ruleInstanceIsNotMutatedByValidation(): void
    {
        $rule = new RegressionPasswordRule();
        $original = $rule->getFailMessage();

        Validator::make(['p' => 'short'], ['p' => $rule])->validate();

        // The constraint is a reusable value object: a failure must not
        // overwrite its default message for every later use.
        $this->assertSame($original, $rule->getFailMessage());
    }

    #[Test]
    public function reusedRuleInstanceStaysCorrectAcrossManyRuns(): void
    {
        $rule = new RegressionPasswordRule();

        $first = Validator::make(['p' => 'x'], ['p' => $rule]);
        $this->assertFalse($first->validate());

        // Mirrors a long-running worker reusing a rule held in a container.
        for ($i = 0; $i < 3; $i++) {
            $validator = Validator::make(['p' => 'Passw0rdLong'], ['p' => $rule]);
            $this->assertTrue($validator->validate());
        }
    }

    // ─── Error message fidelity ──────────────────────────────────────

    #[Test]
    public function distinctViolationMessagesArePreserved(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => [
                new NotBlank(message: 'must not be blank'),
                new Length(min: 5, minMessage: 'must be 5+ chars'),
            ]]
        );

        $validator->validate();

        $this->assertSame(
            ['must not be blank', 'must be 5+ chars'],
            iterator_to_array($validator->errors()->get('name'))
        );
    }

    #[Test]
    public function customMessageIsRecordedOnceNotPerViolation(): void
    {
        $validator = new class (['pw' => [
            new NotBlank(message: 'm1'),
            new Length(min: 8, minMessage: 'm2'),
        ]], ['pw']) extends Validator {
            protected function messages(): array
            {
                return ['pw' => 'Password is invalid'];
            }
        };

        $validator->setData(['pw' => '']);
        $validator->validate();

        $this->assertSame(['Password is invalid'], iterator_to_array($validator->errors()->get('pw')));
    }

    #[Test]
    public function customMessageAppliesToWildcardExpandedKeys(): void
    {
        $validator = new class (['items.*.name' => new NotBlank()], ['items']) extends Validator {
            protected function messages(): array
            {
                return ['items.*.name' => 'Every item needs a name'];
            }
        };

        $validator->setData(['items' => [['name' => 'A'], ['name' => '']]]);
        $validator->validate();

        $this->assertSame('Every item needs a name', $validator->errors()->first('items.1.name'));
    }

    // ─── requiredIf deferral ─────────────────────────────────────────

    #[Test]
    public function requiredIfEvaluatesConditionAtValidationTime(): void
    {
        $state = new stdClass();
        $state->member = false;

        $rule = Rule::requiredIf(fn() => $state->member, 'Name required');

        $this->assertTrue(Validator::make(['name' => null], ['name' => $rule])->validate());

        // The condition changes after the rule object was built.
        $state->member = true;

        $validator = Validator::make(['name' => null], ['name' => $rule]);
        $this->assertFalse($validator->validate());
        $this->assertSame('Name required', $validator->errors()->first('name'));
    }
}

/**
 * A reusable rule whose message varies by failure reason.
 */
class RegressionPasswordRule extends ValidationRule
{
    public string $message = 'Password invalid.';

    public function validate(mixed $value, Closure $fail): void
    {
        if (strlen((string)$value) < 8) {
            $fail('Password too short.');
        } elseif (!preg_match('/\d/', (string)$value)) {
            $fail('Password needs a digit.');
        }
    }
}
