<?php

namespace App\Modules\Item\Models;

use App\Modules\Booking\Models\BookingItem;
use App\Modules\Item\Database\Factories\ItemFactory;
use App\Modules\Item\Enums\ItemStatus;
use App\Shared\Http\Filtering\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A catalog add-on (water, gloves, boots, ...) bookable alongside a field
 * booking — see BookingItem for the per-booking quantity/price snapshot.
 * Global/platform-wide, not scoped to a venue (mirrors PlatformSetting more
 * than Field/Venue in that sense).
 */
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use Filterable, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => ItemStatus::class,
        ];
    }

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
