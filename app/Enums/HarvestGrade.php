<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HarvestGrade: string
{
    use HasOptions;

    case Premium = 'premium';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Premium => __('Premium'),
            self::Good => __('Good'),
            self::Fair => __('Fair'),
            self::Poor => __('Poor'),
        };
    }
}
