<?php

namespace App\Domain\Users\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Freelancer = 'freelancer';
    case Admin = 'admin';
    case Secretary = 'secretary';
}
