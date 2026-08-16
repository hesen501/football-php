<?php

namespace App\Modules\Field\Http\Requests;

use App\Shared\Rules\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreFieldImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('field'));
    }

    public function rules(): array
    {
        return ImageUploadRules::forField('image');
    }
}
