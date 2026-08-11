<?php

namespace App\Modules\Field\Http\Requests;

use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('field'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', Rule::enum(FieldType::class)],
            'capacity' => ['sometimes', 'integer', 'min:2', 'max:50'],
            'hourly_price' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
            'status' => ['sometimes', Rule::enum(FieldStatus::class)],
        ];
    }
}
