<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Simsoft\Validator\Constraints\Custom;
use Simsoft\Validator\Rule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * RuleTest class
 *
 * Unit tests for the Rule helper class.
 */
class RuleTest extends TestCase
{
    #[Test]
    public function makeReturnsConstraintInstance(): void
    {
        $rule = Rule::make(function (mixed $value, Closure $fail): void {
        });

        $this->assertInstanceOf(Constraint::class, $rule);
        $this->assertInstanceOf(Custom::class, $rule);
    }

    #[Test]
    public function makePassesGroupsToConstraint(): void
    {
        $rule = Rule::make(
            function (mixed $value, Closure $fail): void {
            },
            groups: ['login']
        );

        $this->assertContains('login', $rule->groups);
    }

    #[Test]
    public function makeRuleValidatesCorrectly(): void
    {
        $validator = Validator::make(
            ['age' => 15],
            ['age' => Rule::make(function (mixed $value, Closure $fail): void {
                if ($value < 18) {
                    $fail('Must be 18 or older');
                }
            })]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Must be 18 or older', $validator->errors()->first('age'));
    }

    #[Test]
    public function makeRulePassesWithValidValue(): void
    {
        $validator = Validator::make(
            ['age' => 21],
            ['age' => Rule::make(function (mixed $value, Closure $fail): void {
                if ($value < 18) {
                    $fail('Must be 18 or older');
                }
            })]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function requiredIfFailsWhenRequiredAndEmpty(): void
    {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => Rule::requiredIf(true)]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('This field is required.', $validator->errors()->first('field'));
    }

    #[Test]
    public function requiredIfPassesWhenRequiredAndNotEmpty(): void
    {
        $validator = Validator::make(
            ['field' => 'value'],
            ['field' => Rule::requiredIf(true)]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function requiredIfPassesWhenNotRequired(): void
    {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => Rule::requiredIf(false)]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function requiredIfAcceptsCallable(): void
    {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => Rule::requiredIf(fn() => true)]
        );

        $this->assertFalse($validator->validate());
    }

    #[Test]
    public function requiredIfCallableReturnsFalseAllowsEmpty(): void
    {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => Rule::requiredIf(fn() => false)]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function requiredIfUsesCustomMessage(): void
    {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => Rule::requiredIf(true, 'Please fill this in')]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Please fill this in', $validator->errors()->first('field'));
    }

    #[Test]
    public function requiredIfTrimsWhitespaceStrings(): void
    {
        $validator = Validator::make(
            ['field' => '   '],
            ['field' => Rule::requiredIf(true)]
        );

        $this->assertFalse($validator->validate());
    }

    #[Test]
    public function requiredIfPassesGroupsToConstraint(): void
    {
        $rule = Rule::requiredIf(true, groups: ['register']);

        $this->assertContains('register', $rule->groups);
    }

    #[Test]
    public function requiredIfHandlesNullValue(): void
    {
        $validator = Validator::make(
            ['field' => null],
            ['field' => Rule::requiredIf(true)]
        );

        $this->assertFalse($validator->validate());
    }

    // ─── sometimes() ─────────────────────────────────────────────────

    #[Test]
    public function sometimesSkipsValidationWhenValueIsNull(): void
    {
        $validator = Validator::make(
            ['field' => null],
            ['field' => Rule::sometimes(function (mixed $value, Closure $fail) {
                $fail('Should not run');
            })]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function sometimesRunsValidationWhenValueIsPresent(): void
    {
        $validator = Validator::make(
            ['field' => 'abc'],
            ['field' => Rule::sometimes(function (mixed $value, Closure $fail) {
                if (strlen($value) < 5) {
                    $fail('Too short');
                }
            })]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Too short', $validator->errors()->first('field'));
    }

    #[Test]
    public function sometimesPassesWhenValueIsPresentAndValid(): void
    {
        $validator = Validator::make(
            ['field' => 'hello world'],
            ['field' => Rule::sometimes(function (mixed $value, Closure $fail) {
                if (strlen($value) < 5) {
                    $fail('Too short');
                }
            })]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function sometimesAllowsEmptyString(): void
    {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => Rule::sometimes(function (mixed $value, Closure $fail) {
                if ($value === '') {
                    $fail('Empty');
                }
            })]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Empty', $validator->errors()->first('field'));
    }

    // ─── bail() ──────────────────────────────────────────────────────

    #[Test]
    public function bailStopsAtFirstFailure(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => Rule::bail([
                new NotBlank(message: 'Required'),
                new Email(message: 'Invalid email'),
            ])]
        );

        $validator->validate();

        $messages = iterator_to_array($validator->errors()->get('email'));
        $this->assertSame(['Required'], $messages);
    }

    #[Test]
    public function bailPassesWhenAllConstraintsPass(): void
    {
        $validator = Validator::make(
            ['email' => 'test@example.com'],
            ['email' => Rule::bail([
                new NotBlank(message: 'Required'),
                new Email(message: 'Invalid email'),
            ])]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function bailAcceptsGroups(): void
    {
        $rule = Rule::bail([
            new NotBlank(message: 'Required', groups: ['login']),
        ], groups: ['login']);

        $this->assertContains('login', $rule->groups);
    }

    // ─── each() ──────────────────────────────────────────────────────

    #[Test]
    public function eachValidatesEveryArrayItem(): void
    {
        $validator = Validator::make(
            ['tags' => ['php', '', 'js']],
            ['tags' => Rule::each([new NotBlank(message: 'Tag cannot be empty')])],
            ['tags']
        );

        $this->assertFalse($validator->validate());
    }

    #[Test]
    public function eachPassesWhenAllItemsValid(): void
    {
        $validator = Validator::make(
            ['tags' => ['php', 'js', 'go']],
            ['tags' => Rule::each([new NotBlank()])],
            ['tags']
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function eachPassesWithEmptyArray(): void
    {
        $validator = Validator::make(
            ['tags' => []],
            ['tags' => Rule::each([new NotBlank()])],
            ['tags']
        );

        $this->assertTrue($validator->validate());
    }

    // ─── anyOf() ─────────────────────────────────────────────────────

    #[Test]
    public function anyOfPassesWhenOneConstraintMatches(): void
    {
        $validator = Validator::make(
            ['contact' => 'user@example.com'],
            ['contact' => Rule::anyOf([
                new Email(),
                new Regex(pattern: '/^\+?\d+$/'),
            ])]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function anyOfPassesWithSecondConstraint(): void
    {
        $validator = Validator::make(
            ['contact' => '+60123456789'],
            ['contact' => Rule::anyOf([
                new Email(),
                new Regex(pattern: '/^\+?\d+$/'),
            ])]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function anyOfFailsWhenNoConstraintMatches(): void
    {
        $validator = Validator::make(
            ['contact' => 'not-email-or-phone'],
            ['contact' => Rule::anyOf([
                new Email(),
                new Regex(pattern: '/^\+?\d+$/'),
            ], message: 'Must be an email or phone number')]
        );

        $this->assertFalse($validator->validate());
        $this->assertStringContainsString('Must be an email or phone number', $validator->errors()->first('contact'));
    }
}
