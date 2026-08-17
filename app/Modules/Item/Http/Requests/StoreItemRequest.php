<?php

namespace App\Modules\Item\Http\Requests;

use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Item::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'status' => ['sometimes', Rule::enum(ItemStatus::class)],
        ];
    }
}
