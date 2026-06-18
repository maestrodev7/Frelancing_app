<?php

namespace App\Domain\Payments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Escrowed = 'escrowed';
    case Failed = 'failed';
    case Released = 'released';
    case Refunded = 'refunded';
}
