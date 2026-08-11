<?php

namespace App\Modules\Field\Http\Requests;

use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Models\Field;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Field::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(FieldStatus::class)],
            'venue_id' => ['sometimes', 'integer', 'exists:venues,id'],
        ];
    }
}
