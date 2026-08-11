<?php

namespace App\Modules\Booking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared between the admin and customer surfaces — see CancelBookingRequest
 * for the same pattern. Only checks that item_id refers to *some* item;
 * "is it active" is a business rule enforced in BookingService::addItem(),
 * not a validation concern (same split as StoreBookingRequest's field_id
 * vs. BookingService::assertFieldBookable()).
 */
class AddBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageItems', $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
        ];
    }
}
