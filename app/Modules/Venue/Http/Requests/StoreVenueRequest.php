<?php

namespace App\Modules\Venue\Http\Requests;

use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Venue::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['sometimes', Rule::enum(VenueStatus::class)],
            // Only meaningful for SUPER_ADMIN — a VENUE_MANAGER creating a
            // venue is auto-attached as its manager (see VenueService::create).
            'manager_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
