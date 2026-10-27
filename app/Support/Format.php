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
     * Time left until a future moment: "In 45 minutes", "In 9 hours", "In 3 days".
     * Shown in minutes below an hour, hours below a day, otherwise whole days.
     */
    public static function timeUntil(DateTimeInterface $date): string
    {
        $minutes = max(0, (int) floor(now()->diffInMinutes($date)));

        if ($minutes < 60) {
            return trans_choice('{1} In :count minute|[0,*] In :count minutes', $minutes);
        }

        if ($minutes < 60 * 24) {
            return trans_choice('{1} In :count hour|[2,*] In :count hours', intdiv($minutes, 60));
        }

        return trans_choice('{1} In :count day|[2,*] In :count days', intdiv($minutes, 60 * 24));
    }

    /**
     * Format an amount as "IDR 1,250,000".
     */
    public static function money(float|int|string|null $value): string
    {
        return $value === null ? '—' : 'IDR '.number_format((float) $value);
    }
}
