<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\CareLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tree_id
 * @property int|null $user_id
 * @property ActivityType $activity_type
 * @property Carbon $performed_on
 * @property Carbon|null $next_due_on
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tree_id', 'activity_type', 'performed_on', 'next_due_on', 'notes'])]
class CareLog extends Model
{
    /** @use HasFactory<CareLogFactory> */
    use HasFactory, RecordsAuthor;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'performed_on' => 'date',
            'next_due_on' => 'date',
        ];
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
}
