<?php

use PHPUnit\Framework\TestCase;
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Test\Constraints\Password;

/**
 * GenericTest
 */
class GenericTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            'Case 1' => [
                [
                    'email' => [
                        new NotBlank(['message' => 'Email is required']),
                        new Email(['message' => 'Invalid email']),
                    ],
                    'password' => [
                        new Password([
                            'min' => 8,
                            //'max' => 10,
                            'message' => 'Should be at least 5 alphanumeric characters',
                        ]),
                    ]
                ],
                [
                    'email' => 'abcd12312313',
                    'password' => 'adfaasdf23',
                ],
                false,
            ],
            'Case 2' => [
                [
                    'email' => [
                        new NotBlank(['message' => 'Email is required']),
                        new Email(['message' => 'Invalid email']),
                    ],
                    'password' => [
                        new Password([
                            'min' => 8,
                            //'max' => 10,
                            'message' => 'Should be at least 5 alphanumeric characters',
                        ]),
                    ]
                ],
                [
                    'email' => 'abc@gmail.com',
                    'password' => 'adfAas@df23',
                ],
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testInput(array $rules, array $inputs, bool $expected)
    {
        $validator = new Validator($rules);
        $validator->setData($inputs);
        $this->assertEquals($expected, $validator->validate());
        //print_r($validator->getErrors());
    }
}
