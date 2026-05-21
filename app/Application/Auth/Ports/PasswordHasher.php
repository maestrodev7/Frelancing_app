<?php

namespace App\Application\Auth\Ports;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
