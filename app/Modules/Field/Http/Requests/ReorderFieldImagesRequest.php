<?php

namespace App\Modules\Field\Http\Requests;

use App\Modules\Field\Models\Field;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderFieldImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('field'));
    }

    public function rules(): array
    {
        /** @var Field $field */
        $field = $this->route('field');

        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*.id' => [
                'required', 'integer', 'distinct',
                Rule::exists('media', 'id')->where(fn ($query) => $query
                    ->where('model_type', $field->getMorphClass())
                    ->where('model_id', $field->id)),
            ],
            'images.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
