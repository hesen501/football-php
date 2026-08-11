<?php

namespace App\Modules\Field\Http\Requests\Customer;

use App\Modules\Field\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListVenueFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public discovery endpoint — no authentication/authorization needed.
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'type' => ['sometimes', Rule::enum(FieldType::class)],
        ];
    }
}
