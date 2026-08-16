<?php

namespace App\Modules\User\Http\Requests\Admin;

use App\Shared\Rules\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return ImageUploadRules::forField('image');
    }
}
