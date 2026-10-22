<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Display formats shared by every module (PRD 3.1).
 */
class Format
{
    /**
     * Format a date as "21 Oct 2026".
     */
    public static function date(?DateTimeInterface $date): string
    {
        return $date?->format('d M Y') ?? '—';
    }

    /**
     * Format a number as "1,250,000.50".
     */
    public static function number(float|int|string|null $value, int $decimals = 2): string
    {
        return $value === null ? '—' : number_format((float) $value, $decimals);
    }

    /**
     * Format an amount as "IDR 1,250,000".
     */
    public static function money(float|int|string|null $value): string
    {
        return $value === null ? '—' : 'IDR '.number_format((float) $value);
    }
}
