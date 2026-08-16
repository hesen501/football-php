<?php

namespace App\Modules\Field\Models;

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Database\Factories\FieldFactory;
use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Enums\FieldType;
use App\Modules\Venue\Models\Venue;
use App\Shared\Concerns\HasMedia;
use App\Shared\Http\Filtering\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    /** @use HasFactory<FieldFactory> */
    use Filterable, HasFactory, HasMedia, SoftDeletes;

    protected $fillable = [
        'venue_id',
        'name',
        'description',
        'type',
        'capacity',
        'hourly_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'status' => FieldStatus::class,
            'capacity' => 'integer',
            'hourly_price' => 'decimal:2',
        ];
    }

    protected static function newFactory(): FieldFactory
    {
        return FieldFactory::new();
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
