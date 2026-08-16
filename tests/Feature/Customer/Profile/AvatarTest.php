<?php

use App\Modules\Media\Models\Media;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets the authenticated user upload their own avatar', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();

    $response = $this->actingAs($customer, 'sanctum')->postJson('/api/profile/avatar', [
        'image' => UploadedFile::fake()->image('me.jpg', 200, 200),
    ]);

    $response->assertCreated()->assertJsonPath('data.collection', 'avatar');

    $media = Media::findOrFail($response->json('data.id'));
    expect($media->model_type)->toBe($customer->getMorphClass());
    expect($media->model_id)->toBe($customer->id);
    Storage::disk('public')->assertExists($media->path);
});

it('replaces the previous avatar and cleans up the old file', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();

    $first = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/profile/avatar', ['image' => UploadedFile::fake()->image('one.jpg')])
        ->json('data');
    $firstMedia = Media::findOrFail($first['id']);
    $firstPath = $firstMedia->path;

    $second = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/profile/avatar', ['image' => UploadedFile::fake()->image('two.jpg')])
        ->assertCreated()
        ->json('data');

    $this->assertDatabaseMissing('media', ['id' => $firstMedia->id]);
    Storage::disk('public')->assertMissing($firstPath);
    expect(Media::where('model_type', $customer->getMorphClass())->where('model_id', $customer->id)->count())->toBe(1);
    expect($second['id'])->not->toBe($first['id']);
});

it('deletes the authenticated user\'s own avatar', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();

    $uploaded = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/profile/avatar', ['image' => UploadedFile::fake()->image('me.jpg')])
        ->json('data');
    $media = Media::findOrFail($uploaded['id']);

    $this->actingAs($customer, 'sanctum')
        ->deleteJson('/api/profile/avatar')
        ->assertNoContent();

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($media->path);
});

it('requires authentication to upload an avatar', function () {
    Storage::fake('public');

    $this->postJson('/api/profile/avatar', ['image' => UploadedFile::fake()->image('me.jpg')])
        ->assertStatus(401);
});

it('rejects a non-image file as an avatar', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/profile/avatar', ['image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('cannot modify another user\'s avatar through the self-service endpoint', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();
    $other = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/profile/avatar', ['image' => UploadedFile::fake()->image('me.jpg')])
        ->assertCreated();

    // No matter what the acting user uploads, it can only ever attach to
    // their own account — there is no {user} route parameter to redirect
    // it elsewhere (see Customer\AvatarController's docblock).
    expect($other->fresh()->avatarMedia)->toBeNull();
});

it('forbids a customer from managing another user\'s avatar via the admin endpoint', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();
    $other = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/admin/users/{$other->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg')])
        ->assertStatus(403);

    $this->actingAs($customer, 'sanctum')
        ->deleteJson("/api/admin/users/{$other->id}/avatar")
        ->assertStatus(403);
});

it('lets a super admin manage another user\'s avatar via the admin endpoint', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();

    $uploaded = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/users/{$customer->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg')])
        ->assertCreated()
        ->json('data');

    $media = Media::findOrFail($uploaded['id']);
    expect($media->model_id)->toBe($customer->id);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/users/{$customer->id}/avatar")
        ->assertNoContent();

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
});
