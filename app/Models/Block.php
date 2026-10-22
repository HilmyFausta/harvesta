<?php

namespace App\Models;

use Database\Factories\BlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $area_sqm
 * @property string|null $location_note
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'area_sqm', 'location_note', 'description'])]
class Block extends Model
{
    /** @use HasFactory<BlockFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area_sqm' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Tree, $this>
     */
    public function trees(): HasMany
    {
        return $this->hasMany(Tree::class);
    }

    /**
     * @return HasManyThrough<Harvest, Tree, $this>
     */
    public function harvests(): HasManyThrough
    {
        return $this->hasManyThrough(Harvest::class, Tree::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
