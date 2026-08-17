<?php

namespace App\Modules\Field\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Http\Requests\Customer\ListVenueFieldsRequest;
use App\Modules\Field\Http\Resources\FieldPublicResource;
use App\Modules\Field\Models\Field;
use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use App\Shared\Http\Filtering\QueryParams;

class FieldController extends Controller
{
    public function indexForVenue(ListVenueFieldsRequest $request, string $venue)
    {
        $venueModel = Venue::query()
            ->where('status', VenueStatus::ACTIVE->value)
            ->where('slug', $venue)
            ->firstOrFail();

        $params = QueryParams::fromRequest($request);

        $fields = $venueModel->fields()
            ->with(['media', 'coverMedia'])
            ->where('status', FieldStatus::ACTIVE->value)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->applySort($params, ['name', 'hourly_price'], 'name')
            ->paginate($params->perPage, page: $params->page);

        return FieldPublicResource::collection($fields);
    }

    public function show(Field $field)
    {
        if ($field->status !== FieldStatus::ACTIVE || $field->venue->status !== VenueStatus::ACTIVE) {
            abort(404);
        }

        return FieldPublicResource::make($field->load(['venue', 'media', 'coverMedia']));
    }
}
