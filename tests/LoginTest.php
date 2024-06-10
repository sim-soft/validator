<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\LoginValidator;

/**
 * LoginTest
 */
class LoginTest extends TestCase
{
    public static function dataProvider(): array
    {
        return [
            'Valid' => ['abc@gmail.com', 'adfAas@df23', true, ['email' => null, 'password' => null]],
            'Missing email' => ['', 'adfAas@df23', false, ['email' => 'Email is required', 'password' => null]],
            'Invalid email' => ['vz', 'adfaasdf23', false, [
                'email' => 'This value is too short. It should have 10 characters or more.',
                'password' => 'Should be at least 5 alphanumeric characters'
            ]],
            'Invalid password (min)' => ['abc@gmail.com', 'sdf23', false, [
                'email' => null,
                'password' => 'Minimum 8 characters are required'
            ]],
            'Invalid password (max)' => ['abc@gmail.com', 'sdf2111131123sadfsfadfsd234', false, [
                'email' => null,
                'password' => 'Maximum 20 characters exceeded'
            ]],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testInput(string $email, string $password, bool $expected, array $errorsMsg)
    {
        $inputs = [
            'email' => $email,
            'password' => $password,
        ];

        $validator = LoginValidator::make($inputs);

        $this->assertEquals($expected, $validator->passes());
        $this->assertEquals(!$expected, $validator->fails());
        if ($expected) {
            $this->assertSame($inputs, $validator->validated());
            $this->assertTrue($validator->errors()->isEmpty());

            $this->assertSame($inputs['email'], $validator->validated('email'));
            $this->assertSame($inputs['email'], $validator->safe()->only(['email'])['email']);
            $this->assertSame($inputs['password'], $validator->safe()->except(['email'])['password']);

            foreach($validator->safe() as $key => $value) {
                $this->assertSame($inputs[$key], $value);
            }

        } else {
            $this->assertFalse($validator->errors()->isEmpty());

            if ($errorsMsg['email']) {
                $this->assertTrue($validator->errors()->has('email'));
            } else {
                $this->assertFalse($validator->errors()->has('email'));
            }
            $this->assertSame($errorsMsg['email'], $validator->errors()->first('email'));

            if ($errorsMsg['password']) {
                $this->assertTrue($validator->errors()->has('password'));
            } else {
                $this->assertFalse($validator->errors()->has('password'));
            }
            $this->assertSame($errorsMsg['password'], $validator->errors()->first('password'));

            foreach($validator->errors()->get('email') as $message) {
                $this->assertSame($errorsMsg['email'], $message);
            }

            foreach($validator->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->assertSame($errorsMsg[$key], $message);
                }
            }
        }

    }

}
