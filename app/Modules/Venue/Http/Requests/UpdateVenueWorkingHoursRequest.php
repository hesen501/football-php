<?php

namespace App\Modules\Venue\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('venue'));
    }

    public function rules(): array
    {
        return [
            // Exactly one entry per day of the week (0=Sunday..6=Saturday,
            // matching Carbon's ->dayOfWeek) — 'distinct' + 'between:0,6' +
            // 'size:7' together guarantee the full set {0..6} appears once each.
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'days.*.is_closed' => ['required', 'boolean'],
            'days.*.opens_at' => ['nullable', 'date_format:H:i'],
            'days.*.closes_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * Cross-field checks that don't fit the flat rule list above: an open
     * day needs both times, a closed day needs neither to be wrong, and
     * closes_at must be strictly after opens_at. Done here rather than via
     * required_if/after with `days.*.` targets, which only resolve sibling
     * fields at the same array index for a narrower set of rules than these.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            foreach ((array) $this->input('days', []) as $index => $day) {
                $isClosed = $day['is_closed'] ?? null;
                $opensAt = $day['opens_at'] ?? null;
                $closesAt = $day['closes_at'] ?? null;

                if ($isClosed !== false && $isClosed !== true) {
                    continue; // already flagged by the 'boolean' rule above.
                }

                if ($isClosed) {
                    continue; // a closed day's opens_at/closes_at are ignored either way.
                }

                if (! $opensAt) {
                    $validator->errors()->add("days.{$index}.opens_at", 'An open day needs an opens_at time.');
                }

                if (! $closesAt) {
                    $validator->errors()->add("days.{$index}.closes_at", 'An open day needs a closes_at time.');
                }

                if ($opensAt && $closesAt && $closesAt <= $opensAt) {
                    $validator->errors()->add("days.{$index}.closes_at", 'closes_at must be after opens_at.');
                }
            }
        });
    }
}
