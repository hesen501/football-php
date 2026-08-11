<?php

use App\Modules\User\Models\User;

it('returns the authenticated admin profile', function () {
    $admin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@test.com')
        ->assertJsonPath('data.roles.0', 'SUPER_ADMIN');
});

it('rejects unauthenticated access to me', function () {
    $this->getJson('/api/admin/auth/me')->assertStatus(401);
});
