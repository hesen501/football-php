<?php

use App\Modules\User\Models\User;

it('returns the authenticated customer profile', function () {
    $customer = User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'customer@test.com');
});

it('rejects unauthenticated access to me', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});

it('logs the current customer out and revokes the token', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk();

    expect($customer->tokens()->count())->toBe(0);
});

it('does not let an admin-panel-only guard block a customer token here', function () {
    // Customer routes intentionally have no admin.panel-style restriction —
    // any authenticated user (including admins/managers) may use them.
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/auth/me')
        ->assertOk();
});
