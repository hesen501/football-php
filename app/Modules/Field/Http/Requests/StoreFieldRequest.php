<?php

namespace App\Modules\Field\Http\Requests;

use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Enums\FieldType;
use App\Modules\Field\Models\Field;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Field::class, $this->route('venue')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(FieldType::class)],
            'capacity' => ['required', 'integer', 'min:2', 'max:50'],
            'hourly_price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'status' => ['sometimes', Rule::enum(FieldStatus::class)],
        ];
    }
}
