<?php

namespace App\Modules\Booking\DTOs;

final class BookingPriceBreakdown
{
    public function __construct(
        public readonly float $hourlyPrice,
        public readonly float $totalPrice,
        public readonly float $commissionRate,
        public readonly float $commissionAmount,
        public readonly float $venueAmount,
    ) {}
}
