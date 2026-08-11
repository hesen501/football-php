<?php

namespace App\Modules\User\Http\Requests;

use App\Modules\User\Enums\UserStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(['SUPER_ADMIN', 'VENUE_MANAGER', 'CUSTOMER'])],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
        ];
    }
}
