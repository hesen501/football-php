<?php

namespace App\Modules\Venue\Http\Requests;

use App\Shared\Rules\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreVenueImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('venue'));
    }

    public function rules(): array
    {
        return ImageUploadRules::forField('image');
    }
}
