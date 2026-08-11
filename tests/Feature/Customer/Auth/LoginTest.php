<?php

use App\Modules\User\Models\User;

it('logs a customer in and returns a token', function () {
    User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/auth/login', [
        'email' => 'customer@test.com',
        'password' => 'password',
    ])->assertOk()->assertJsonPath('data.user.email', 'customer@test.com');
});

it('lets a venue manager log into the customer app too (multi-role)', function () {
    User::factory()->venueManager()->create(['email' => 'manager@test.com']);

    $this->postJson('/api/auth/login', [
        'email' => 'manager@test.com',
        'password' => 'password',
    ])->assertOk();
});

it('rejects an unknown email', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'nobody@test.com',
        'password' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects a wrong password', function () {
    User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/auth/login', [
        'email' => 'customer@test.com',
        'password' => 'wrong',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects a suspended account', function () {
    User::factory()->customer()->suspended()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/auth/login', [
        'email' => 'customer@test.com',
        'password' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('does not require a verified email to log in', function () {
    User::factory()->customer()->unverified()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/auth/login', [
        'email' => 'customer@test.com',
        'password' => 'password',
    ])->assertOk();
});
