<?php

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Database\Factories\BookingFactory;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use App\Shared\Http\Filtering\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use Filterable, HasFactory;

    // No SoftDeletes — bookings are historical/financial records that are
    // never deleted, only transitioned through $status.

    protected $fillable = [
        'user_id', 'field_id', 'venue_id',
        'start_time', 'end_time', 'duration_minutes',
        'hourly_price', 'total_price', 'commission_rate', 'commission_amount', 'venue_amount',
        'source', 'status', 'payment_status',
        'payment_reference', 'payment_provider',
        'cancelled_at', 'cancellation_reason', 'cancelled_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'hourly_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'venue_amount' => 'decimal:2',
            'source' => BookingSource::class,
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * Ordered by id (= insertion/add order) explicitly — without this,
     * Postgres has no guaranteed row order, and specifically will often
     * return a just-updated row *last* (an UPDATE frequently can't be done
     * in-place and instead appends a new physical tuple version at the end
     * of the table). Omitting this ordering is what made incrementing an
     * item's quantity look like it "sorted" that item to the bottom of the
     * list on the next fetch — it was really just Postgres's unordered scan
     * order shifting underneath an ORDER BY-less query.
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class)->orderBy('id');
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED], true)
            && $this->start_time->isFuture();
    }

    /**
     * Same window as isCancellable() — items only make sense to add/remove
     * before the booking has happened and while it hasn't been cancelled.
     */
    public function canModifyItems(): bool
    {
        return $this->isCancellable();
    }

    /**
     * Sum of booking_items.total_price — the price snapshot taken when each
     * item was added, never the item's current catalog price.
     */
    public function itemsTotal(): float
    {
        return round($this->bookingItems->sum(fn (BookingItem $bookingItem) => (float) $bookingItem->total_price), 2);
    }

    /**
     * base field-booking price (total_price) + items — what the customer
     * actually owes. total_price itself stays a pure field-booking snapshot
     * (see the bookings migration); this is a derived read, never stored.
     */
    public function grandTotal(): float
    {
        return round((float) $this->total_price + $this->itemsTotal(), 2);
    }
}
