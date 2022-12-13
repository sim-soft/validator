<?php

namespace Test;

use Simsoft\Constraints\Custom;
use Simsoft\Validator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Test\Constraints\Password;

class LoginValidator extends Validator
{
    protected array $attributes = ['email', 'password', 'remember_me' => true];

    protected function rules(): array
    {
        return [
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
                /*new Custom(function($value, &$message) {
                    if (preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\da-zA-Z])(.{8,20})$/', $value, $matches)) {
                        return true;
                    }
                    $message = 'Invalid password';
                    return false;
                }),*/
            ],
        ];
    }
}
