<?php

namespace App\Modules\User\Http\Requests;

use App\Modules\User\Enums\UserStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', Rule::in(['SUPER_ADMIN', 'VENUE_MANAGER', 'CUSTOMER'])],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
        ];
    }
}
