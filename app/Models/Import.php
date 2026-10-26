<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportSource;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['file_name', 'range_from', 'range_to', 'format', 'source', 'lanes_upserted', 'entries_created', 'entries_replaced', 'user_id'])]
final class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'range_from' => 'date:Y-m-d',
            'range_to' => 'date:Y-m-d',
            'source' => ImportSource::class,
        ];
    }
}
