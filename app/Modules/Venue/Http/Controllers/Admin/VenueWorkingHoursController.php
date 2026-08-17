<?php

namespace App\Modules\Venue\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Venue\Http\Requests\UpdateVenueWorkingHoursRequest;
use App\Modules\Venue\Http\Resources\VenueWorkingHourResource;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Services\VenueService;

class VenueWorkingHoursController extends Controller
{
    public function __construct(private readonly VenueService $venues) {}

    public function index(Venue $venue)
    {
        $this->authorize('view', $venue);

        return VenueWorkingHourResource::collection($this->venues->getWorkingHours($venue));
    }

    public function update(UpdateVenueWorkingHoursRequest $request, Venue $venue)
    {
        $hours = $this->venues->setWorkingHours($venue, $request->validated()['days']);

        return VenueWorkingHourResource::collection($hours);
    }
}
