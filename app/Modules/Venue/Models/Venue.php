<?php

namespace App\Modules\Venue\Models;

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Database\Factories\VenueFactory;
use App\Modules\Venue\Enums\VenueStatus;
use App\Shared\Http\Filtering\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use Filterable, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'latitude',
        'longitude',
        'phone',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'status' => VenueStatus::class,
        ];
    }

    protected static function newFactory(): VenueFactory
    {
        return VenueFactory::new();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Co-managers of this venue. Many-to-many: see venue_managers migration.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'venue_managers');
    }

    public function isManagedBy(User $user): bool
    {
        return $this->managers->contains($user);
    }
}
