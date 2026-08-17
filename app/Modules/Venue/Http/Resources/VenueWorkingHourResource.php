<?php

namespace App\Modules\Venue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueWorkingHourResource extends JsonResource
{
    private const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function toArray(Request $request): array
    {
        return [
            'day_of_week' => $this->day_of_week,
            'day_name' => self::DAY_NAMES[$this->day_of_week] ?? null,
            'is_closed' => $this->is_closed,
            'opens_at' => $this->is_closed ? null : $this->formatTime($this->opens_at),
            'closes_at' => $this->is_closed ? null : $this->formatTime($this->closes_at),
        ];
    }

    /**
     * Postgres returns a `time` column as "HH:MM:SS" — trimmed to "HH:MM" to
     * match the format the update endpoint accepts.
     */
    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
