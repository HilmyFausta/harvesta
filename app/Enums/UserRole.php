<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Staff = 'staff';
    case Viewer = 'viewer';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Staff => __('Staff'),
            self::Viewer => __('Viewer'),
        };
    }
}
