<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Quotation;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Document numbers in the formats the business already used:
 *
 *   quotation          2026/SPL/2710-001        (daily counter)
 *   DO, DP invoice     2026/X/SPL/2710-001      (one daily counter shared by both)
 *   final invoice      reuses its DO's number
 *   sales order        SO-{quotation number}
 *   inquiry            INQ/2026/1027-001        (daily counter)
 */
final class DocumentNumber
{
    public static function quotation(Customer $customer, ?CarbonInterface $at = null): string
    {
        $at ??= now();
        $n = self::next('quotation:'.$at->format('Y-m-d'));

        return sprintf('%s/%s/%s-%03d', $at->format('Y'), $customer->code, $at->format('dm'), $n);
    }

    public static function shipping(Customer $customer, ?CarbonInterface $at = null): string
    {
        $at ??= now();
        $n = self::next('shipping:'.$at->format('Y-m-d'));

        return sprintf(
            '%s/%s/%s/%s-%03d',
            $at->format('Y'),
            self::roman((int) $at->format('n')),
            $customer->code,
            $at->format('dm'),
            $n,
        );
    }

    public static function salesOrder(Quotation $quotation): string
    {
        return 'SO-'.$quotation->number;
    }

    public static function inquiry(?CarbonInterface $at = null): string
    {
        $at ??= now();
        $n = self::next('inquiry:'.$at->format('Y-m-d'));

        return sprintf('INQ/%s/%s-%03d', $at->format('Y'), $at->format('md'), $n);
    }

    public static function roman(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1];
    }

    /**
     * Bump a named counter in one statement, so two requests can never be
     * handed the same value (SQLite 3.35+ for RETURNING).
     */
    private static function next(string $name): int
    {
        $row = DB::selectOne(
            'insert into document_sequences (name, last_value) values (?, 1)
             on conflict (name) do update set last_value = last_value + 1
             returning last_value',
            [$name],
        );

        return (int) $row->last_value;
    }
}
