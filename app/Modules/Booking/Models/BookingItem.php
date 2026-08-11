<?php

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Database\Factories\BookingItemFactory;
use App\Modules\Item\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line item on a booking: "this many of this item, at the price it was
 * when first added". See BookingService::addItem()/removeItem() for the
 * only code paths that write to this table.
 */
class BookingItem extends Model
{
    /** @use HasFactory<BookingItemFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'item_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    protected static function newFactory(): BookingItemFactory
    {
        return BookingItemFactory::new();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
