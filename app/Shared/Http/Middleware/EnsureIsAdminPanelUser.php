<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every /api/admin/* route as a whole, on top of the fine-grained
 * permission/policy checks each endpoint already does.
 *
 * Why this exists: CUSTOMER intentionally holds permissions like
 * bookings.create/bookings.cancel — they're needed for the future customer
 * app (Phase 8) — so a permission check alone can't tell the admin surface
 * and the customer surface apart. A CUSTOMER's Sanctum token must never
 * authorize *any* /api/admin/* action, regardless of which permissions
 * their role happens to carry.
 */
class EnsureIsAdminPanelUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasAnyRole(['SUPER_ADMIN', 'VENUE_MANAGER'])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return $next($request);
    }
}
