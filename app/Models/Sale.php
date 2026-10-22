<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $harvest_id
 * @property int|null $user_id
 * @property string $buyer_name
 * @property Carbon $sold_on
 * @property string $quantity
 * @property string $unit_price
 * @property string $total_amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['harvest_id', 'buyer_name', 'sold_on', 'quantity', 'unit_price', 'notes'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, RecordsAuthor;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        // total_amount is never taken from user input (PRD F-09).
        static::saving(function (Sale $sale): void {
            $sale->total_amount = number_format(
                (float) $sale->quantity * (float) $sale->unit_price, 2, '.', '',
            );
        });
    }

    /**
     * @return BelongsTo<Harvest, $this>
     */
    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
