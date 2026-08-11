<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Field\Models\Field;
use App\Shared\Http\Responses\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Not namespaced under Admin/ or Customer/ — this endpoint is wired into
 * both admin (now) and customer (Phase 8) route groups unchanged. Viewing
 * availability reveals no financial data, so it isn't ownership-gated the
 * way create/update/cancel are.
 */
class FieldAvailabilityController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function __invoke(Request $request, Field $field)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['date'])->startOfDay();

        $slots = collect($this->bookings->availability($field, $date))->map(fn (array $slot) => [
            'start_time' => $slot['start_time']->toIso8601String(),
            'end_time' => $slot['end_time']->toIso8601String(),
            'available' => $slot['available'],
        ]);

        return ApiResponse::success([
            'field_id' => $field->id,
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }
}
