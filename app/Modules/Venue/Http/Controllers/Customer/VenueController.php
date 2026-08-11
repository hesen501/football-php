<?php

namespace App\Modules\Venue\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Http\Requests\Customer\ListPublicVenuesRequest;
use App\Modules\Venue\Http\Resources\VenuePublicResource;
use App\Modules\Venue\Models\Venue;
use App\Shared\Http\Filtering\QueryParams;

class VenueController extends Controller
{
    public function index(ListPublicVenuesRequest $request)
    {
        $params = QueryParams::fromRequest($request);

        $venues = Venue::query()
            ->with('workingHours')
            ->where('status', VenueStatus::ACTIVE->value)
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->string('city')))
            ->applySearch($params, ['name', 'city', 'address'])
            ->applySort($params, ['name', 'city', 'created_at'], 'name')
            ->paginate($params->perPage, page: $params->page);

        return VenuePublicResource::collection($venues);
    }

    /**
     * Keyed by slug, not id — nicer public URLs, and reuses the column that
     * exists specifically for this (see the venues migration).
     */
    public function show(string $venue)
    {
        $venue = Venue::query()
            ->with('workingHours')
            ->where('status', VenueStatus::ACTIVE->value)
            ->where('slug', $venue)
            ->firstOrFail();

        return VenuePublicResource::make($venue);
    }
}
