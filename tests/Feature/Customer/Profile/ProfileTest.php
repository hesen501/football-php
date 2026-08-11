<?php

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('shows the authenticated user\'s own profile', function () {
    $customer = User::factory()->customer()->create(['email' => 'customer@test.com']);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('data.email', 'customer@test.com');
});

it('updates name and phone', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/profile', ['name' => 'New Name', 'phone' => '+994501234567'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('resets email verification when the email changes and sends a new link', function () {
    Notification::fake();
    $customer = User::factory()->customer()->create(['email' => 'old@test.com']);

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/profile', ['email' => 'new@test.com'])
        ->assertOk()
        ->assertJsonPath('data.email', 'new@test.com')
        ->assertJsonPath('data.email_verified_at', null);

    expect($customer->fresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($customer->fresh(), VerifyEmail::class);
});

it('does not reset verification when the email is unchanged', function () {
    $customer = User::factory()->customer()->create(['email' => 'same@test.com']);

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/profile', ['email' => 'same@test.com', 'name' => 'New Name'])
        ->assertOk();

    expect($customer->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('changes the password when the current password is correct', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/profile', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

    expect(Hash::check('new-password-123', $customer->fresh()->password))->toBeTrue();
});

it('rejects a password change with the wrong current password', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/profile', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
});

it('rejects an email already taken by another user', function () {
    User::factory()->customer()->create(['email' => 'taken@test.com']);
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/profile', ['email' => 'taken@test.com'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('requires authentication', function () {
    $this->getJson('/api/profile')->assertStatus(401);
});
