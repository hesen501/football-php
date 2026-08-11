<?php

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('registers a new customer and returns a token', function () {
    Notification::fake();

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Rashad Aliyev',
        'email' => 'rashad@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'roles']]])
        ->assertJsonPath('data.user.email', 'rashad@test.com')
        ->assertJsonPath('data.user.roles.0', 'CUSTOMER');

    $user = User::query()->where('email', 'rashad@test.com')->firstOrFail();
    expect($user->hasRole('CUSTOMER'))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('validates required fields', function () {
    $this->postJson('/api/auth/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('requires password confirmation to match', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Someone',
        'email' => 'someone@test.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('rejects a duplicate email', function () {
    User::factory()->customer()->create(['email' => 'taken@test.com']);

    $this->postJson('/api/auth/register', [
        'name' => 'Someone',
        'email' => 'taken@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});
