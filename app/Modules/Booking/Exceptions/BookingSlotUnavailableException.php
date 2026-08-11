<?php

namespace App\Modules\Booking\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class BookingSlotUnavailableException extends BusinessRuleException
{
    public function __construct(string $message = 'The selected time slot is no longer available.')
    {
        parent::__construct($message, 'BOOKING_SLOT_UNAVAILABLE', 409);
    }
}
