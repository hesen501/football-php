<?php

namespace App\Modules\Booking\Http\Requests;

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $field = Field::query()->find($this->input('field_id'));

        return $this->user()->can('create', [Booking::class, $field]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'start_time' => ['required', 'date', 'after:now'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $start = $this->date('start_time');

            if ($start && ($start->minute !== 0 || $start->second !== 0)) {
                $validator->errors()->add('start_time', 'Bookings must start exactly on the hour (e.g. 18:00).');
            }
        });
    }
}
