<?php

namespace App\Modules\User\Http\Requests;

use App\Modules\User\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['sometimes', 'string', Password::min(8)],
            'role' => ['sometimes', Rule::in(['SUPER_ADMIN', 'VENUE_MANAGER', 'CUSTOMER'])],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
        ];
    }
}
