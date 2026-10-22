<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Fill the author column with the authenticated user on create, unless it
 * was set explicitly (e.g. by a seeder).
 *
 * @mixin Model
 */
trait RecordsAuthor
{
    protected static function bootRecordsAuthor(): void
    {
        static::creating(function (self $model): void {
            $column = $model->authorColumn();

            if ($model->getAttribute($column) === null && Auth::check()) {
                $model->setAttribute($column, Auth::id());
            }
        });
    }

    /**
     * Get the name of the column that stores the author.
     */
    public function authorColumn(): string
    {
        return 'user_id';
    }
}
