<?php

namespace App\Modules\Venue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageManagers', $this->route('venue'));
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
