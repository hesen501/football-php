<?php

namespace App\Modules\Booking\DTOs;

use App\Modules\Booking\Enums\BookingSource;
use Carbon\CarbonImmutable;

/**
 * Carries a booking request from the HTTP layer into BookingService,
 * independent of whether it came from the admin panel or (later) the
 * customer app — only `source` and `userId` differ between the two.
 */
final class CreateBookingData
{
    public function __construct(
        public readonly int $userId,
        public readonly int $fieldId,
        public readonly CarbonImmutable $startTime,
        public readonly int $durationHours,
        public readonly BookingSource $source,
        public readonly ?string $notes = null,
    ) {}
}
