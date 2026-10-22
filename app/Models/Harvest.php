<?php

namespace App\Models;

use App\Enums\HarvestGrade;
use App\Enums\HarvestUnit;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\HarvestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tree_id
 * @property int|null $user_id
 * @property Carbon $harvested_on
 * @property string $quantity
 * @property HarvestUnit $unit
 * @property HarvestGrade $grade
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tree_id', 'harvested_on', 'quantity', 'unit', 'grade', 'notes'])]
class Harvest extends Model
{
    /** @use HasFactory<HarvestFactory> */
    use HasFactory, RecordsAuthor;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harvested_on' => 'date',
            'quantity' => 'decimal:2',
            'unit' => HarvestUnit::class,
            'grade' => HarvestGrade::class,
        ];
    }

    /**
     * Get the quantity that has not been sold yet.
     *
     * Uses the `sales_sum_quantity` attribute when loaded via
     * withSum('sales', 'quantity') to avoid an extra query per row.
     */
    public function remainingQuantity(): float
    {
        $sold = $this->sales_sum_quantity ?? $this->sales()->sum('quantity');

        return round((float) $this->quantity - (float) $sold, 2);
    }

    /**
     * @return BelongsTo<Tree, $this>
     */
    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
