<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Simsoft\Validator\Constraints\Custom;
use Simsoft\Validator\Constraints\ValidationRule;

/**
 * CustomConstraintValidatorTest class
 *
 * Integration-level tests for CustomConstraintValidator via the Validator.
 */
class CustomConstraintValidatorTest extends TestCase
{
    #[Test]
    public function validationPassesWhenConstraintPasses(): void
    {
        $validator = Validator::make(
            ['field' => 'good'],
            ['field' => new Custom(function (mixed $value, Closure $fail): void {
            })]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function validationFailsWhenConstraintFails(): void
    {
        $validator = Validator::make(
            ['field' => 'bad'],
            ['field' => new Custom(function (mixed $value, Closure $fail): void {
                $fail('Not allowed');
            })]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Not allowed', $validator->errors()->first('field'));
    }

    #[Test]
    public function validationHandlesNullValue(): void
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
        $this->assertSame('Cannot be null', $validator->errors()->first('field'));
    }

    #[Test]
    public function validationPassesWithCustomValidationRuleSubclass(): void
    {
        $rule = new class () extends ValidationRule {
            public function __construct()
            {
                parent::__construct(null);
            }

            public function validate(mixed $value, Closure $fail): void
            {
                if ($value !== 'secret') {
                    $fail('Wrong value');
                }
            }
        };

        $validator = Validator::make(
            ['code' => 'secret'],
            ['code' => $rule]
        );

        $this->assertTrue($validator->validate());
    }

    #[Test]
    public function validationFailsWithCustomValidationRuleSubclass(): void
    {
        $rule = new class () extends ValidationRule {
            public function __construct()
            {
                parent::__construct(null);
            }

            public function validate(mixed $value, Closure $fail): void
            {
                if ($value !== 'secret') {
                    $fail('Wrong value');
                }
            }
        };

        $validator = Validator::make(
            ['code' => 'wrong'],
            ['code' => $rule]
        );

        $this->assertFalse($validator->validate());
        $this->assertSame('Wrong value', $validator->errors()->first('code'));
    }
}
