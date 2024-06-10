<?php

namespace Test;

use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;
use Test\Constraints\Password;

class LoginValidator extends Validator
{
    protected array $attributes = ['email', 'password', 'remember_me' => true];

    /**
     * {@inheritdoc }
     */
    protected function rules(): array
    {
        return [
            'email' => new Sequentially([
                new NotBlank(message: 'Email is required'),
                new Length(['min' => 10, 'max' => 150]),
                new Email(message: 'Invalid email'),
            ]),
            'password' =>
                new Password([
                    'min' => 8,
                    //'max' => 10,
                    'message' => 'Should be at least 5 alphanumeric characters',
                ])
                /*new Custom(function(mixed $value, \Closure $fail) {
                    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/', $value, $matches)) {
                        $fail('Invalid password.');
                    }
                })*/
            ,
        ];
    }
}
