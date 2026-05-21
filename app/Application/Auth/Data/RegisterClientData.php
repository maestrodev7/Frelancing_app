<?php

namespace App\Application\Auth\Data;

readonly class RegisterClientData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $password,
        public string $billingAddress,
        public int $countryId,
        public string $timezone,
    ) {}
}
