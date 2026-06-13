<?php

namespace App\Domain\Contact\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
}
