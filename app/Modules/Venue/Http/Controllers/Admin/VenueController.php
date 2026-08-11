<?php

namespace App\Modules\Venue\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Venue\Http\Requests\ListVenuesRequest;
use App\Modules\Venue\Http\Requests\StoreVenueRequest;
use App\Modules\Venue\Http\Requests\UpdateVenueRequest;
use App\Modules\Venue\Http\Resources\VenueResource;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Services\VenueService;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Http\Response;

class VenueController extends Controller
{
    public function __construct(private readonly VenueService $venues) {}

    public function index(ListVenuesRequest $request)
    {
        $params = QueryParams::fromRequest($request);
        $paginated = $this->venues->list($request->user(), $params, $request->only(['status', 'manager_id']));

        return VenueResource::collection($paginated);
    }

    public function store(StoreVenueRequest $request)
    {
        $venue = $this->venues->create($request->user(), $request->validated());

        return VenueResource::make($venue)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Venue $venue)
    {
        $this->authorize('view', $venue);

        return VenueResource::make($venue->load(['managers', 'workingHours']));
    }

    public function update(UpdateVenueRequest $request, Venue $venue)
    {
        $venue = $this->venues->update($venue, $request->validated());

        return VenueResource::make($venue);
    }

    public function destroy(Venue $venue)
    {
        $this->authorize('delete', $venue);

        $this->venues->delete($venue);

        return response()->noContent();
    }
}
