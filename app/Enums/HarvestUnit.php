<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HarvestUnit: string
{
    use HasOptions;

    case Kg = 'kg';
    case Pcs = 'pcs';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Kg => __('kg'),
            self::Pcs => __('pcs'),
        };
    }
}
