<?php

namespace App\Modules\Field\Services;

use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Pagination\LengthAwarePaginator;

class FieldService
{
    /** @param array{status?: string, venue_id?: int} $filters */
    public function list(User $actor, QueryParams $params, array $filters): LengthAwarePaginator
    {
        return Field::query()
            ->with('venue')
            // A VENUE_MANAGER only sees fields belonging to venues they
            // manage; SUPER_ADMIN (the only other role that can reach this
            // method) sees everything.
            ->when(! $actor->hasRole('SUPER_ADMIN'), fn ($query) => $query->whereHas(
                'venue.managers',
                fn ($managers) => $managers->whereKey($actor->id),
            ))
            ->when($filters['venue_id'] ?? null, fn ($query, $venueId) => $query->where('venue_id', $venueId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->applySearch($params, ['name', 'description'])
            ->applySort($params, ['name', 'hourly_price', 'created_at'], '-created_at')
            ->paginate($params->perPage, page: $params->page);
    }

    /** @param array{name: string, description?: string|null, type: string, capacity: int, hourly_price: float, status?: string} $data */
    public function create(Venue $venue, array $data): Field
    {
        return $venue->fields()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'capacity' => $data['capacity'],
            'hourly_price' => $data['hourly_price'],
            'status' => $data['status'] ?? FieldStatus::ACTIVE->value,
        ])->load('venue');
    }

    /** @param array{name?: string, description?: string|null, type?: string, capacity?: int, hourly_price?: float, status?: string} $data */
    public function update(Field $field, array $data): Field
    {
        // hourly_price changes here never touch existing bookings — Booking
        // snapshots hourly_price/total_price/commission at creation time.
        $field->fill(array_intersect_key($data, array_flip([
            'name', 'description', 'type', 'capacity', 'hourly_price', 'status',
        ])));

        $field->save();

        return $field->load('venue');
    }

    public function delete(Field $field): void
    {
        $field->delete();
    }
}
