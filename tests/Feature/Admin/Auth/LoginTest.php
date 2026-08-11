<?php

use App\Modules\User\Models\User;

it('logs an admin in and returns a token', function () {
    User::factory()->superAdmin()->create(['email' => 'admin@test.com']);

    $response = $this->postJson('/api/admin/auth/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'roles']]])
        ->assertJsonPath('data.user.email', 'admin@test.com');
});

it('logs a venue manager in and returns a token', function () {
    User::factory()->venueManager()->create(['email' => 'manager@test.com']);

    $this->postJson('/api/admin/auth/login', [
        'email' => 'manager@test.com',
        'password' => 'password',
    ])->assertOk();
});

it('rejects a login with an unknown email', function () {
    $this->postJson('/api/admin/auth/login', [
        'email' => 'nobody@test.com',
        'password' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects a login with a wrong password', function () {
    User::factory()->superAdmin()->create(['email' => 'admin@test.com']);

    $this->postJson('/api/admin/auth/login', [
        'email' => 'admin@test.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects a suspended admin account', function () {
    User::factory()->superAdmin()->suspended()->create(['email' => 'admin@test.com']);

    $this->postJson('/api/admin/auth/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects a customer-only account from the admin panel', function () {
    User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/admin/auth/login', [
        'email' => 'customer@test.com',
        'password' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('validates the login payload', function () {
    $this->postJson('/api/admin/auth/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});
