<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Simsoft\Validator\Rule;

/**
 * CustomValidatorTest class
 */
class CustomValidatorTest extends TestCase
{
    public static function dataProvider(): array
    {
        return [
            'Alphanumeric only' => [
                [
                    'keywords' => Rule::make(function(mixed $value, Closure $fail) {
                        $format = '/^\w+$/';
                        if (!preg_match($format, $value, $matches)) {
                            $fail('Invalid value');
                        }
                    })
                ],
                [
                    'keywords' => 'abcd12345',
                ],
                true,
                null
            ],
            'Alphanumeric special characters' => [
                [
                    'keywords' => Rule::make(function(mixed $value, Closure $fail) {
                        $format = '/^\w+$/';
                        if (!preg_match($format, $value, $matches)) {
                            $fail('Invalid value');
                        }
                    })
                ],
                [
                    'keywords' => 'abcd12345@#$%',
                ],
                false,
                'Invalid value'
            ],
            'Required if not empty' => [
                [
                    'keywords' => Rule::requiredIf(true)
                ],
                [
                    'keywords' => 'aa',
                ],
                true,
                null
            ],
            'Required if empty' => [
                [
                    'keywords' => Rule::requiredIf(true)
                ],
                [
                    'keywords' => '',
                ],
                false,
                'This field is required.',
            ],
            'Not required if not empty' => [
                [
                    'keywords' => Rule::requiredIf(false)
                ],
                [
                    'keywords' => 'aa',
                ],
                true,
                null,
            ],
            'Not required if empty' => [
                [
                    'keywords' => Rule::requiredIf(fn() => false)
                ],
                [
                    'keywords' => '',
                ],
                true,
                null,
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testValidator(array $rules, array $inputs, bool $expected, ?string $errorMsg): void
    {
        $validator = Validator::make($inputs, $rules);
        $this->assertSame($inputs, $validator->all());
        $this->assertEquals($expected, $validator->validate());
        $this->assertEquals($expected, $validator->passes());
        $this->assertEquals(!$expected, $validator->fails());
        if ($expected) {
            $this->assertSame($inputs, $validator->validated());
            $this->assertTrue($validator->errors()->isEmpty());
            foreach ($validator->safe() as $key => $value) {
                $this->assertSame($value, $inputs[$key]);
            }
        } else {
            $this->assertFalse($validator->errors()->isEmpty());
            $this->assertSame($errorMsg, $validator->errors()->first('keywords'));

            foreach($validator->errors()->get('keywords') as $message) {
                $this->assertSame($errorMsg, $message);
            }
        }
    }

}
