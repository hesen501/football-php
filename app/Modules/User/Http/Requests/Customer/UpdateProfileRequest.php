<?php

namespace App\Modules\User\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Always the authenticated user's own record — no ownership check
        // needed beyond "is logged in", already enforced by auth:sanctum.
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['sometimes', 'string', Password::min(8), 'confirmed'],
            // Self-service password changes require proving you already
            // know the current one — admin-initiated changes (UserService::update)
            // don't need this since an admin acting on someone else's
            // account is a different trust boundary.
            'current_password' => ['required_with:password', 'string', 'current_password'],
        ];
    }
}
