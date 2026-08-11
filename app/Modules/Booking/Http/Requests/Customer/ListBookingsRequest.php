<?php

namespace App\Modules\Booking\Http\Requests\Customer;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Always scoped to the authenticated user's own bookings (see
        // BookingService::listForCustomer) — no permission check needed
        // beyond "is logged in", already enforced by the auth:sanctum route.
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'status' => ['sometimes', Rule::enum(BookingStatus::class)],
            'payment_status' => ['sometimes', Rule::enum(PaymentStatus::class)],
        ];
    }
}
