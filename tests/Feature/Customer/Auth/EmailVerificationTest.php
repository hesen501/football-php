<?php

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function signedVerificationUrl(User $user): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);
}

it('verifies the email via a valid signed link', function () {
    $customer = User::factory()->customer()->unverified()->create();

    $this->getJson(signedVerificationUrl($customer))->assertOk();

    expect($customer->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a link with a tampered signature', function () {
    $customer = User::factory()->customer()->unverified()->create();
    $url = signedVerificationUrl($customer).'&tampered=1';

    $this->getJson($url)->assertStatus(403);

    expect($customer->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a link with a mismatched hash', function () {
    $customer = User::factory()->customer()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $customer->id,
        'hash' => sha1('wrong-email@test.com'),
    ]);

    $this->getJson($url)->assertStatus(400)->assertJsonPath('error_code', 'INVALID_VERIFICATION_LINK');

    expect($customer->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('is idempotent when the email is already verified', function () {
    $customer = User::factory()->customer()->create(); // verified by default factory state

    $this->getJson(signedVerificationUrl($customer))
        ->assertOk()
        ->assertJsonPath('data.message', 'Email already verified.');
});

it('lets an authenticated unverified customer resend the verification email', function () {
    Notification::fake();
    $customer = User::factory()->customer()->unverified()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/auth/email/resend')
        ->assertOk();

    Notification::assertSentTo($customer, VerifyEmail::class);
});

it('does not resend if already verified', function () {
    Notification::fake();
    $customer = User::factory()->customer()->create(); // verified

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/auth/email/resend')
        ->assertOk()
        ->assertJsonPath('data.message', 'Email already verified.');

    Notification::assertNothingSent();
});
