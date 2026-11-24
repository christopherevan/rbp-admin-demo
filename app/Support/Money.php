<?php

namespace App\Support;

final class Money
{
    /** Rp 1.234.567, as written in Indonesia. */
    public static function idr(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
