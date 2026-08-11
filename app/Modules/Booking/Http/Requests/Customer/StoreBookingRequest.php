<?php

namespace App\Modules\Booking\Http\Requests\Customer;

use App\Modules\Booking\Models\Booking;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No Field context passed (unlike the admin StoreBookingRequest) —
        // a customer never owns a venue, so BookingPolicy::create() falls
        // through to a flat permission check for the $field === null case.
        return $this->user()->can('create', Booking::class);
    }

    public function rules(): array
    {
        return [
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
