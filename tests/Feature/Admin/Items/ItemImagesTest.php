<?php

use App\Modules\Item\Models\Item;
use App\Modules\Media\Models\Media;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets a super admin upload an item image', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/admin/items/{$item->id}/image", [
        'image' => UploadedFile::fake()->image('water.jpg', 200, 200),
    ]);

    $response->assertCreated()->assertJsonPath('data.collection', 'image');

    $media = Media::findOrFail($response->json('data.id'));
    expect($media->model_type)->toBe($item->getMorphClass());
    Storage::disk('public')->assertExists($media->path);
});

it('replaces the existing item image and removes the old file', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $first = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", ['image' => UploadedFile::fake()->image('one.jpg')])
        ->json('data');
    $firstMedia = Media::findOrFail($first['id']);
    $firstPath = $firstMedia->path;
    Storage::disk('public')->assertExists($firstPath);

    $second = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", ['image' => UploadedFile::fake()->image('two.jpg')])
        ->assertCreated()
        ->json('data');

    // The old row is gone and its file was cleaned up — only the new one remains.
    $this->assertDatabaseMissing('media', ['id' => $firstMedia->id]);
    Storage::disk('public')->assertMissing($firstPath);
    expect(Media::where('model_type', $item->getMorphClass())->where('model_id', $item->id)->count())->toBe(1);
    expect($second['id'])->not->toBe($first['id']);
});

it('deletes an item image and removes the physical file', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $uploaded = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", ['image' => UploadedFile::fake()->image('water.jpg')])
        ->json('data');
    $media = Media::findOrFail($uploaded['id']);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/items/{$item->id}/image")
        ->assertNoContent();

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($media->path);
});

it('deleting an item with no image is a no-op, not an error', function () {
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/items/{$item->id}/image")
        ->assertNoContent();
});

it('forbids a venue manager from uploading an item image', function () {
    Storage::fake('public');
    $manager = User::factory()->venueManager()->create();
    $item = Item::factory()->create();

    // items.* is intentionally not granted to VENUE_MANAGER — see ItemPolicy.
    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(403);
});

it('forbids a customer from uploading an item image', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();
    $item = Item::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(403);
});

it('rejects a non-image file upload for an item', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('rejects an item image over the configured size limit', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/items/{$item->id}/image", [
            'image' => UploadedFile::fake()->image('big.jpg')->size(config('media.max_kilobytes') + 100),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});
