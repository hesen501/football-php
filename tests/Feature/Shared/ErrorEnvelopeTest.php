<?php

use App\Modules\User\Models\User;

/**
 * Regression coverage for two bugs only found by running the app for real
 * (not by the rest of the suite, which always uses getJson()/postJson() —
 * those force an `Accept: application/json` header that happened to mask
 * both issues):
 *
 * 1. Laravel's Handler::prepareException() silently converts
 *    AuthorizationException into Symfony's AccessDeniedHttpException
 *    *before* any render() callback runs — a callback registered against
 *    AuthorizationException itself is dead code. See bootstrap/app.php.
 * 2. Auth\Middleware\Authenticate falls back to route('login') for a guest
 *    request that doesn't send an explicit Accept header — fatal in an
 *    API-only app with no such route. See bootstrap/app.php's
 *    redirectGuestsTo(null).
 */
it('returns a clean 401 envelope for a guest request with no Accept header at all', function () {
    // Deliberately NOT using getJson() — this is the exact condition that
    // triggered "Route [login] not defined." as an uncaught 500.
    $this->get('/api/bookings')
        ->assertStatus(401)
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('returns a clean 403 envelope, not a raw exception trace, for an authorization failure', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->get('/api/admin/users')
        ->assertStatus(403)
        ->assertExactJson(['message' => 'This action is unauthorized.']);
});

it('returns a clean 404 envelope for an unknown route', function () {
    $this->get('/api/this-route-does-not-exist')
        ->assertStatus(404)
        ->assertExactJson(['message' => 'Resource not found.']);
});
