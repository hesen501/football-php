<?php

namespace App\Modules\Venue\Models;

use App\Modules\Venue\Database\Factories\VenueWorkingHourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single day's opening window for a venue. Always managed as a full set
 * of 7 (one per day_of_week, 0=Sunday..6=Saturday) — see
 * VenueService::setWorkingHours() / seedDefaultWorkingHours().
 */
class VenueWorkingHour extends Model
{
    /** @use HasFactory<VenueWorkingHourFactory> */
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'day_of_week',
        'is_closed',
        'opens_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    protected static function newFactory(): VenueWorkingHourFactory
    {
        return VenueWorkingHourFactory::new();
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
