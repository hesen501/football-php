<?php

namespace App\Modules\User\Models;

use App\Modules\Booking\Models\Booking;
use App\Modules\User\Database\Factories\UserFactory;
use App\Modules\User\Enums\UserStatus;
use App\Modules\Venue\Models\Venue;
use App\Shared\Concerns\HasMedia;
use App\Shared\Http\Filtering\Filterable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use Filterable, HasApiTokens, HasFactory, HasMedia, HasRoles, MustVerifyEmail, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Venues this user manages (as a VENUE_MANAGER). Many-to-many: a manager
     * can co-manage several venues, a venue can have several managers.
     */
    public function managedVenues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'venue_managers');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }
}
