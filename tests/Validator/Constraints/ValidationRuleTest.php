<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator\Constraints\CustomConstraintValidator;
use Simsoft\Validator\Constraints\ValidationRule;

/**
 * ValidationRuleTest class
 *
 * Unit tests for the abstract ValidationRule constraint.
 */
class ValidationRuleTest extends TestCase
{
    #[Test]
    public function validatedByReturnsCustomConstraintValidatorClass(): void
    {
        $rule = $this->createConcreteRule(fn() => null);
        $this->assertSame(CustomConstraintValidator::class, $rule->validatedBy());
    }

    #[Test]
    public function performValidationReturnsTrueWhenNoFailCalled(): void
    {
        $rule = $this->createConcreteRule(function (mixed $value, Closure $fail): void {
            // pass — do nothing
        });

        $rule->withValue('valid');
        $this->assertTrue($rule->performValidation());
    }

    #[Test]
    public function performValidationReturnsFalseWhenFailCalled(): void
    {
        $rule = $this->createConcreteRule(function (mixed $value, Closure $fail): void {
            $fail('Something went wrong');
        });

        $rule->withValue('invalid');
        $this->assertFalse($rule->performValidation());
    }

    #[Test]
    public function getFailMessageReturnsDefaultBeforeValidation(): void
    {
        $rule = $this->createConcreteRule(fn() => null);
        $this->assertSame('Invalid {{ value }}.', $rule->getFailMessage());
    }

    #[Test]
    public function getFailMessageReturnsCustomMessageAfterFail(): void
    {
        $rule = $this->createConcreteRule(function (mixed $value, Closure $fail): void {
            $fail('Custom error');
        });

        $rule->withValue('x');
        $rule->performValidation();

        $this->assertSame('Custom error', $rule->getFailMessage());
    }

    #[Test]
    public function setFailMessageOverridesMessage(): void
    {
        $rule = $this->createConcreteRule(fn() => null);
        $rule->setFailMessage('Overridden');

        $this->assertSame('Overridden', $rule->getFailMessage());
    }

    #[Test]
    public function withValueReturnsSelfForChaining(): void
    {
        $rule = $this->createConcreteRule(fn() => null);
        $result = $rule->withValue('test');

        $this->assertSame($rule, $result);
    }

    #[Test]
    public function performValidationPassesCorrectValueToValidate(): void
    {
        $received = null;
        $rule = $this->createConcreteRule(function (mixed $value, Closure $fail) use (&$received): void {
            $received = $value;
        });

        $rule->withValue('hello');
        $rule->performValidation();

        $this->assertSame('hello', $received);
    }

    #[Test]
    public function performValidationHandlesNullValue(): void
    {
        $received = 'not-null';
        $rule = $this->createConcreteRule(function (mixed $value, Closure $fail) use (&$received): void {
            $received = $value;
        });

        $rule->withValue(null);
        $rule->performValidation();

        $this->assertNull($received);
    }

    #[Test]
    public function performValidationResetsPassedStateOnReuse(): void
    {
        $shouldFail = true;
        $rule = $this->createConcreteRule(function (mixed $value, Closure $fail) use (&$shouldFail): void {
            if ($shouldFail) {
                $fail('Failed');
            }
        });

        $rule->withValue('x');
        $this->assertFalse($rule->performValidation());

        $shouldFail = false;
        $rule->withValue('y');
        $this->assertTrue($rule->performValidation());
    }

    /**
     * Create a concrete implementation of the abstract ValidationRule.
     *
     * @param Closure $callback
     * @return ValidationRule
     */
    private function createConcreteRule(Closure $callback): ValidationRule
    {
        return new class ($callback) extends ValidationRule {
            public function __construct(private Closure $callback)
            {
                parent::__construct(null);
            }

            public function validate(mixed $value, Closure $fail): void
            {
                ($this->callback)($value, $fail);
            }
        };
    }
}
