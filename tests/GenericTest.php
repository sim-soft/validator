<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;
use Test\Constraints\Password;

/**
 * GenericTest
 */
class GenericTest extends TestCase
{
    public static function dataProvider(): array
    {
        return [
            'Case 1' => [
                [
                    'email' => new Sequentially([
                        new NotBlank(['message' => 'Email is required']),
                        new Email(['message' => 'Invalid email']),
                    ]),
                    'password' => new Password([
                        'min' => 8,
                        //'max' => 10,
                        'message' => 'Should be at least 5 alphanumeric characters',
                    ]),
                ],
                [
                    'email' => 'InvalidEmailTest_12312313',
                    'password' => 'adfaasdf23',
                ],
                false,
            ],
            'Case 2' => [
                [
                    'email' => new Sequentially([
                        /*new NotBlank(message: 'Email is required'),
                        new Email(message: 'Invalid email'),*/
                        new NotBlank(['message' => 'Email is required']),
                        new Email(['message' => 'Invalid email']),
                    ]),
                    'password' =>
                        new Password([
                            'min' => 8,
                            //'max' => 10,
                            'message' => 'Should be at least 5 alphanumeric characters',
                        ]),

                ],
                [
                    'email' => 'abc@gmail.com',
                    'password' => 'adfAas@df23',
                ],
                true,
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testInput(array $rules, array $inputs, bool $expected)
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
            $this->assertSame('Invalid email', $validator->errors()->first('email'));

            foreach($validator->errors()->get('email') as $message) {
                $this->assertSame('Invalid email', $message);
            }
        }
    }
}
