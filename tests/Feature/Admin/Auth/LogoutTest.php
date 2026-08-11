<?php

use App\Modules\User\Models\User;

it('logs the current admin out and revokes the token', function () {
    $admin = User::factory()->superAdmin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/admin/auth/logout')
        ->assertOk();

    expect($admin->tokens()->count())->toBe(0);
});

it('rejects logout without a token', function () {
    $this->postJson('/api/admin/auth/logout')->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});
