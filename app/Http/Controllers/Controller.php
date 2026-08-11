<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Shared base for every module controller. FormRequests handle their own
 * authorization via ->authorize() (see StoreUserRequest etc.), so only
 * AuthorizesRequests (for ->authorize() calls on simple show/destroy actions
 * that don't need a FormRequest) is pulled in here — no validation trait
 * needed since we don't validate directly in controllers.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
