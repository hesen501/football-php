<?php

namespace App\Modules\Venue\Services;

use App\Modules\User\Models\User;
use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Models\VenueWorkingHour;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VenueService
{
    // Seeded for every day of a newly created venue — see
    // seedDefaultWorkingHours(). Chosen as a reasonable default football-venue
    // day; managers can narrow it (or close specific days) afterwards via
    // setWorkingHours().
    private const DEFAULT_OPENS_AT = '08:00';

    private const DEFAULT_CLOSES_AT = '23:00';

    /** @param array{status?: string, manager_id?: int} $filters */
    public function list(User $actor, QueryParams $params, array $filters): LengthAwarePaginator
    {
        return Venue::query()
            ->with(['managers', 'workingHours', 'media', 'coverMedia'])
            // A VENUE_MANAGER only ever sees venues they co-manage; SUPER_ADMIN
            // sees everything (and reaches this method at all only because
            // Gate::before already granted the viewAny check).
            ->when(! $actor->hasRole('SUPER_ADMIN'), fn ($query) => $query->whereHas(
                'managers',
                fn ($managers) => $managers->whereKey($actor->id),
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['manager_id'] ?? null, fn ($query, $managerId) => $query->whereHas(
                'managers',
                fn ($managers) => $managers->whereKey($managerId),
            ))
            ->applySearch($params, ['name', 'city', 'address'])
            ->applySort($params, ['name', 'city', 'created_at'], '-created_at')
            ->paginate($params->perPage, page: $params->page);
    }

    /** @param array{name: string, description?: string|null, address: string, city: string, latitude?: float|null, longitude?: float|null, phone?: string|null, email?: string|null, status?: string, manager_id?: int|null} $data */
    public function create(User $actor, array $data): Venue
    {
        return DB::transaction(function () use ($actor, $data) {
            $venue = Venue::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'status' => $data['status'] ?? VenueStatus::ACTIVE->value,
            ]);

            if ($actor->hasRole('VENUE_MANAGER') && ! $actor->hasRole('SUPER_ADMIN')) {
                // Self-service: the creating manager becomes the venue's first manager.
                $venue->managers()->attach($actor->id);
            } elseif (! empty($data['manager_id'])) {
                $this->attachManager($venue, (int) $data['manager_id']);
            }

            $this->seedDefaultWorkingHours($venue);

            return $venue->load(['managers', 'workingHours', 'media', 'coverMedia']);
        });
    }

    /** @param array{name?: string, description?: string|null, address?: string, city?: string, latitude?: float|null, longitude?: float|null, phone?: string|null, email?: string|null, status?: string} $data */
    public function update(Venue $venue, array $data): Venue
    {
        // Slug is intentionally never regenerated on rename — keeps existing
        // links/URLs to the venue stable even after a name change.
        $venue->fill(array_intersect_key($data, array_flip([
            'name', 'description', 'address', 'city', 'latitude', 'longitude', 'phone', 'email', 'status',
        ])));

        $venue->save();

        return $venue->load(['managers', 'workingHours', 'media', 'coverMedia']);
    }

    public function delete(Venue $venue): void
    {
        $venue->delete();
    }

    public function attachManager(Venue $venue, int $userId): void
    {
        if ($venue->managers()->whereKey($userId)->exists()) {
            throw new BusinessRuleException('This user already manages this venue.', 'ALREADY_A_MANAGER');
        }

        $venue->managers()->attach($userId);

        $manager = User::query()->find($userId);

        if ($manager && ! $manager->hasRole('VENUE_MANAGER')) {
            $manager->assignRole('VENUE_MANAGER');
        }
    }

    public function detachManager(Venue $venue, User $user): void
    {
        if ($venue->managers()->count() <= 1) {
            throw new BusinessRuleException('A venue must have at least one manager.', 'LAST_MANAGER');
        }

        $venue->managers()->detach($user->id);
    }

    public function getWorkingHours(Venue $venue): Collection
    {
        return $venue->workingHours()->get();
    }

    /**
     * Replaces all 7 days in one go — the request layer (see
     * UpdateVenueWorkingHoursRequest) requires exactly one entry per
     * day_of_week 0-6, so there's never a partial update to reconcile.
     *
     * @param  array<int, array{day_of_week: int, is_closed: bool, opens_at?: string|null, closes_at?: string|null}>  $days
     */
    public function setWorkingHours(Venue $venue, array $days): Collection
    {
        return DB::transaction(function () use ($venue, $days) {
            foreach ($days as $day) {
                VenueWorkingHour::query()->updateOrCreate(
                    ['venue_id' => $venue->id, 'day_of_week' => $day['day_of_week']],
                    [
                        'is_closed' => $day['is_closed'],
                        // A closed day never keeps stale hours around.
                        'opens_at' => $day['is_closed'] ? null : $day['opens_at'],
                        'closes_at' => $day['is_closed'] ? null : $day['closes_at'],
                    ],
                );
            }

            return $venue->workingHours()->get();
        });
    }

    /**
     * Every day open 08:00-23:00 by default — see the DEFAULT_* constants.
     * Only runs for venues created through create() above; factories/seeders
     * that write venues directly skip this (BookingService treats a venue
     * with zero working-hours rows as unrestricted).
     */
    private function seedDefaultWorkingHours(Venue $venue): void
    {
        $now = now();

        $rows = array_map(fn (int $day) => [
            'venue_id' => $venue->id,
            'day_of_week' => $day,
            'is_closed' => false,
            'opens_at' => self::DEFAULT_OPENS_AT,
            'closes_at' => self::DEFAULT_CLOSES_AT,
            'created_at' => $now,
            'updated_at' => $now,
        ], range(0, 6));

        VenueWorkingHour::query()->insert($rows);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'venue';
        $slug = $base;
        $suffix = 2;

        // withTrashed(): a unique DB index doesn't know about soft deletes,
        // so a slug used by a soft-deleted venue is still taken.
        while (Venue::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
