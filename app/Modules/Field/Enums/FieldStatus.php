<?php

namespace App\Modules\Field\Enums;

enum FieldStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case MAINTENANCE = 'MAINTENANCE';
}
