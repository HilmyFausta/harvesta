<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $block_id
 * @property int|null $user_id
 * @property ExpenseCategory $category
 * @property string $amount
 * @property Carbon $spent_on
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['block_id', 'category', 'amount', 'spent_on', 'description'])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, RecordsAuthor;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'spent_on' => 'date',
        ];
    }

    /**
     * The block this expense belongs to; null means a general expense.
     *
     * @return BelongsTo<Block, $this>
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
