<?php

namespace App\Modules\Venue\Http\Requests;

use App\Modules\Venue\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderVenueImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('venue'));
    }

    public function rules(): array
    {
        /** @var Venue $venue */
        $venue = $this->route('venue');

        return [
            'images' => ['required', 'array', 'min:1'],
            // Scoped to this venue's own media — an id belonging to another
            // venue (or to a different collection type entirely) fails
            // validation here rather than being silently accepted.
            'images.*.id' => [
                'required', 'integer', 'distinct',
                Rule::exists('media', 'id')->where(fn ($query) => $query
                    ->where('model_type', $venue->getMorphClass())
                    ->where('model_id', $venue->id)),
            ],
            'images.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
