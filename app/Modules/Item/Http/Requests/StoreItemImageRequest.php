<?php

namespace App\Modules\Item\Http\Requests;

use App\Shared\Rules\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    public function rules(): array
    {
        return ImageUploadRules::forField('image');
    }
}
