<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One lane's summary card for one day. No activity log: an import writes cards in bulk, and its
 * imports row is the trail of what changed.
 */
#[Fillable(['date', 'lane_id', 'summary', 'sources', 'import_id'])]
final class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Lane, $this>
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class);
    }

    /**
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // date:Y-m-d, never a bare 'date': that serialises as UTC midnight and shows the
            // day before for anyone east of UTC.
            'date' => 'date:Y-m-d',
            'sources' => 'array',
        ];
    }
}
