<?php

namespace App\Modules\Booking\Http\Requests;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Booking::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'status' => ['sometimes', Rule::enum(BookingStatus::class)],
            'payment_status' => ['sometimes', Rule::enum(PaymentStatus::class)],
            'venue_id' => ['sometimes', 'integer', 'exists:venues,id'],
            'field_id' => ['sometimes', 'integer', 'exists:fields,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }
}
