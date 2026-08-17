<?php

namespace App\Modules\Item\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ListPublicItemsRequest extends FormRequest
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
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
