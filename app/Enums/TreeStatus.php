<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TreeStatus: string
{
    use HasOptions;

    case Seedling = 'seedling';
    case Growing = 'growing';
    case Productive = 'productive';
    case NeedsAttention = 'needs_attention';
    case Dead = 'dead';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Seedling => __('Seedling'),
            self::Growing => __('Growing'),
            self::Productive => __('Productive'),
            self::NeedsAttention => __('Needs attention'),
            self::Dead => __('Dead'),
        };
    }

    /**
     * Get the Flux badge color for this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Seedling => 'blue',
            self::Growing => 'lime',
            self::Productive => 'green',
            self::NeedsAttention => 'yellow',
            self::Dead => 'red',
        };
    }

    /**
     * Determine whether the tree may receive new care logs and harvests.
     */
    public function acceptsRecords(): bool
    {
        return $this !== self::Dead;
    }
}
