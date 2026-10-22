<?php

namespace App\Models;

use Database\Factories\TreeTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $scientific_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['category_id', 'name', 'scientific_name', 'description'])]
class TreeType extends Model
{
    /** @use HasFactory<TreeTypeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Soft-deleted trees are excluded by default, but they still hold the
     * foreign key, so use withTrashed() when checking whether this type
     * can be deleted.
     *
     * @return HasMany<Tree, $this>
     */
    public function trees(): HasMany
    {
        return $this->hasMany(Tree::class);
    }
}
