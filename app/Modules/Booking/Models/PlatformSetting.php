<?php

namespace App\Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getCommissionRate(): float
    {
        $value = static::query()->where('key', 'commission_rate')->value('value');

        return (float) ($value ?? config('booking.commission_rate'));
    }
}
