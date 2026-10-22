<?php

namespace App\Models;

use App\Enums\TreeStatus;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\TreeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $code
 * @property int $tree_type_id
 * @property int $block_id
 * @property int|null $created_by
 * @property Carbon $planted_on
 * @property TreeStatus $status
 * @property string|null $height_cm
 * @property string|null $notes
 * @property string|null $photo_path
 * @property-read string|null $photo_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['code', 'tree_type_id', 'block_id', 'planted_on', 'status', 'height_cm', 'notes', 'photo_path'])]
class Tree extends Model
{
    /** @use HasFactory<TreeFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'seedling',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planted_on' => 'date',
            'status' => TreeStatus::class,
            'height_cm' => 'decimal:2',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Tree $tree): void {
            $tree->code = blank($tree->code) ? static::nextCode() : $tree->code;
        });

        // Remove the old file when a photo is replaced or removed.
        static::updated(function (Tree $tree): void {
            $old = $tree->getOriginal('photo_path');

            if ($tree->wasChanged('photo_path') && filled($old)) {
                Storage::disk('photos')->delete($old);
            }
        });

        static::forceDeleted(function (Tree $tree): void {
            if (filled($tree->photo_path)) {
                Storage::disk('photos')->delete($tree->photo_path);
            }
        });
    }

    /**
     * Get the name of the column that stores the author.
     */
    public function authorColumn(): string
    {
        return 'created_by';
    }

    /**
     * Generate the next sequential tree code, e.g. "T-0042".
     */
    public static function nextCode(): string
    {
        $last = static::withTrashed()
            ->where('code', 'like', 'T-%')
            ->orderByDesc('id')
            ->value('code');

        $number = $last ? ((int) substr($last, 2)) + 1 : 1;

        do {
            $code = sprintf('T-%04d', $number++);
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the public URL of the tree photo.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => filled($this->photo_path)
                ? Storage::disk('photos')->url($this->photo_path)
                : null,
        );
    }

    /**
     * Determine whether the tree may receive new care logs and harvests.
     */
    public function acceptsRecords(): bool
    {
        return $this->status->acceptsRecords();
    }

    /**
     * @return BelongsTo<TreeType, $this>
     */
    public function treeType(): BelongsTo
    {
        return $this->belongsTo(TreeType::class);
    }

    /**
     * @return BelongsTo<Block, $this>
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CareLog, $this>
     */
    public function careLogs(): HasMany
    {
        return $this->hasMany(CareLog::class);
    }

    /**
     * @return HasMany<Harvest, $this>
     */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }
}
