<?php

namespace App\Modules\Dashboard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Http\Resources\BookingResource;
use App\Modules\Dashboard\Services\DashboardService;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function stats(Request $request)
    {
        // No model instance involved — 'dashboard.view' is checked directly
        // as a permission-backed Gate ability (spatie/laravel-permission
        // registers permissions as abilities automatically); a dedicated
        // Policy class would add nothing here.
        $this->authorize('dashboard.view');

        return ApiResponse::success($this->dashboard->stats($request->user()));
    }

    public function recentBookings(Request $request)
    {
        $this->authorize('dashboard.view');

        $bookings = $this->dashboard->recentBookings($request->user(), (int) $request->query('limit', 10));

        return BookingResource::collection($bookings);
    }
}
