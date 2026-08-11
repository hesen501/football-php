<?php

namespace App\Modules\Field\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Field\Http\Requests\ListFieldsRequest;
use App\Modules\Field\Http\Requests\StoreFieldRequest;
use App\Modules\Field\Http\Requests\UpdateFieldRequest;
use App\Modules\Field\Http\Resources\FieldResource;
use App\Modules\Field\Models\Field;
use App\Modules\Field\Services\FieldService;
use App\Modules\Venue\Models\Venue;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Http\Response;

class FieldController extends Controller
{
    public function __construct(private readonly FieldService $fields) {}

    public function index(ListFieldsRequest $request)
    {
        $params = QueryParams::fromRequest($request);
        $paginated = $this->fields->list($request->user(), $params, $request->only(['status', 'venue_id']));

        return FieldResource::collection($paginated);
    }

    public function store(StoreFieldRequest $request, Venue $venue)
    {
        $field = $this->fields->create($venue, $request->validated());

        return FieldResource::make($field)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Field $field)
    {
        $this->authorize('view', $field);

        return FieldResource::make($field->load('venue'));
    }

    public function update(UpdateFieldRequest $request, Field $field)
    {
        $field = $this->fields->update($field, $request->validated());

        return FieldResource::make($field);
    }

    public function destroy(Field $field)
    {
        $this->authorize('delete', $field);

        $this->fields->delete($field);

        return response()->noContent();
    }
}
