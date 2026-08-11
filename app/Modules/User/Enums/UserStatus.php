<?php

namespace App\Modules\User\Enums;

enum UserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
}
