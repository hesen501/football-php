<?php

namespace App\Modules\Venue\Http\Requests;

use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListVenuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Venue::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(VenueStatus::class)],
            'manager_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
