<?php

namespace App\Modules\Item\Http\Requests;

use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Item::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ItemStatus::class)],
        ];
    }
}
