<?php

namespace App\Modules\Booking\Enums;

enum BookingSource: string
{
    case CUSTOMER_APP = 'CUSTOMER_APP';
    case ADMIN_PANEL = 'ADMIN_PANEL';
}
