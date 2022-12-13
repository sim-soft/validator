<?php

use PHPUnit\Framework\TestCase;
use Test\LoginValidator;


class LoginTest extends TestCase
{

    public function dataProvider(): array
    {
        return [
            'Valid' => ['abc@gmail.com', 'adfAas@df23', true],
            'Invalid email' => ['vz', 'adfaasdf23', false],
            'Invalid password (min)' => ['abc@gmail.com', 'sdf23', false],
            'Invalid password (max)' => ['abc@gmail.com', 'sdf2111131123sadfsfadfsd234', false],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testInput($email, $password, $expected)
    {
        $validator = new LoginValidator();
        $status = $validator->setData([
                        'email' => $email,
                        'password' => $password,
                    ])
                    ->validate();

        print_r($validator->getErrors());

        $this->assertEquals($expected, $status);
    }

}
