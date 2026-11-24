<?php

namespace App\Enums;

enum InvoiceType: string
{
    case DownPayment = 'down_payment';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::DownPayment => 'Down payment invoice',
            self::Final => 'Invoice',
        };
    }
}
