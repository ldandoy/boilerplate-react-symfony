<?php

namespace App\ApiResource;

class RegisterOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public string $message = 'User registered successfully'
    ) {
    }
}
