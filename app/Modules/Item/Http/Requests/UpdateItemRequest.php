<?php

namespace App\Modules\Item\Http\Requests;

use App\Modules\Item\Enums\ItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
            'status' => ['sometimes', Rule::enum(ItemStatus::class)],
        ];
    }
}
