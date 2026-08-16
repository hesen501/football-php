<?php

namespace App\Modules\User\Http\Requests\Customer;

use App\Shared\Rules\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Always the authenticated user's own avatar — no ownership check
        // needed beyond "is logged in" (see UpdateProfileRequest, same pattern).
        return true;
    }

    public function rules(): array
    {
        return ImageUploadRules::forField('image');
    }
}
