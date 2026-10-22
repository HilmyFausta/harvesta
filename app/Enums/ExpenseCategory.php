<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExpenseCategory: string
{
    use HasOptions;

    case Fertilizer = 'fertilizer';
    case Pesticide = 'pesticide';
    case Labor = 'labor';
    case Equipment = 'equipment';
    case Water = 'water';
    case Transport = 'transport';
    case Other = 'other';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fertilizer => __('Fertilizer'),
            self::Pesticide => __('Pesticide'),
            self::Labor => __('Labor'),
            self::Equipment => __('Equipment'),
            self::Water => __('Water'),
            self::Transport => __('Transport'),
            self::Other => __('Other'),
        };
    }
}
