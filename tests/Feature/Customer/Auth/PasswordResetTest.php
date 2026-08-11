<?php

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a reset link for a registered email', function () {
    Notification::fake();
    $customer = User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/auth/forgot-password', ['email' => 'customer@test.com'])
        ->assertOk();

    Notification::assertSentTo($customer, ResetPassword::class);
});

it('gives the same generic response for an unregistered email, without sending anything', function () {
    Notification::fake();

    $response = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@test.com']);

    $response->assertOk();
    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $customer = User::factory()->customer()->create(['email' => 'customer@test.com']);
    $token = Password::createToken($customer);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'customer@test.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertOk();

    expect(Hash::check('new-password-123', $customer->fresh()->password))->toBeTrue();
});

it('rejects an invalid or expired token', function () {
    User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->postJson('/api/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'customer@test.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertStatus(400)->assertJsonPath('error_code', 'INVALID_RESET_TOKEN');
});

it('can log in with the new password after a reset', function () {
    $customer = User::factory()->customer()->create(['email' => 'customer@test.com']);
    $token = Password::createToken($customer);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'customer@test.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertOk();

    $this->postJson('/api/auth/login', [
        'email' => 'customer@test.com',
        'password' => 'new-password-123',
    ])->assertOk();
});
