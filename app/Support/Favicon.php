<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Picks the browser tab icon for the current page (files in public/favicons).
 */
class Favicon
{
    /**
     * Route name prefixes mapped to their icon, matching the sidebar icons.
     *
     * @var array<string, string>
     */
    private const ICONS = [
        'dashboard' => 'dashboard',
        'trees.' => 'trees',
        'care-logs.' => 'care-logs',
        'harvests.' => 'harvests',
        'categories.' => 'categories',
        'tree-types.' => 'tree-types',
        'blocks.' => 'blocks',
        'expenses.' => 'expenses',
        'sales.' => 'sales',
        'reports.' => 'reports',
        'users.' => 'users',
        'profile.' => 'settings',
        'appearance.' => 'settings',
        'security.' => 'settings',
    ];

    /**
     * The icon URL for the given (or current) route name.
     */
    public static function url(?string $routeName = null): string
    {
        $routeName ??= Route::currentRouteName() ?? '';

        foreach (self::ICONS as $prefix => $icon) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return asset("favicons/{$icon}.svg");
            }
        }

        return asset('favicon.svg');
    }
}
