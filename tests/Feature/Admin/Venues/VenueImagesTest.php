<?php

use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('lets a super admin upload a venue image into the gallery', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/admin/venues/{$venue->id}/images", [
        'image' => UploadedFile::fake()->image('photo.jpg', 300, 300),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.collection', 'gallery')
        ->assertJsonPath('data.mime_type', 'image/jpeg');

    $media = Media::findOrFail($response->json('data.id'));
    expect($media->model_type)->toBe($venue->getMorphClass());
    expect($media->model_id)->toBe($venue->id);
    Storage::disk('public')->assertExists($media->path);
});

it('lets a venue manager who manages the venue upload an image', function () {
    Storage::fake('public');
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertCreated();
});

it('lists every image belonging to a venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    Media::factory()->for($venue, 'model')->collection(MediaCollection::COVER)->create();
    Media::factory()->for($venue, 'model')->collection(MediaCollection::GALLERY)->create();
    Media::factory()->for(Venue::factory()->create(), 'model')->collection(MediaCollection::GALLERY)->create(); // another venue's image

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/venues/{$venue->id}/images")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('deletes a venue image and removes the physical file', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $uploaded = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->json('data');

    $media = Media::findOrFail($uploaded['id']);
    Storage::disk('public')->assertExists($media->path);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/venues/{$venue->id}/images/{$media->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($media->path);
});

it('refuses to delete media belonging to a different venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $otherVenue = Venue::factory()->create();
    $media = Media::factory()->for($otherVenue, 'model')->collection(MediaCollection::GALLERY)->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/venues/{$venue->id}/images/{$media->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('media', ['id' => $media->id]);
});

it('sets a gallery image as the cover, demoting the previous cover back to the gallery', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $oldCover = Media::factory()->for($venue, 'model')->collection(MediaCollection::COVER)->create();
    $newCover = Media::factory()->for($venue, 'model')->collection(MediaCollection::GALLERY)->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}/images/{$newCover->id}/cover")
        ->assertOk()
        ->assertJsonPath('data.collection', 'cover');

    expect($oldCover->fresh()->collection)->toBe(MediaCollection::GALLERY);
    expect($newCover->fresh()->collection)->toBe(MediaCollection::COVER);
    expect($venue->media()->where('collection', MediaCollection::COVER->value)->count())->toBe(1);
});

it('never allows two cover images for the same venue at the database level', function () {
    $venue = Venue::factory()->create();
    $now = now();

    DB::table('media')->insert([
        'model_type' => $venue->getMorphClass(),
        'model_id' => $venue->id,
        'collection' => 'cover',
        'disk' => 'public',
        'path' => "venue/{$venue->id}/cover/one.jpg",
        'mime_type' => 'image/jpeg',
        'size' => 100,
        'sort_order' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    expect(fn () => DB::table('media')->insert([
        'model_type' => $venue->getMorphClass(),
        'model_id' => $venue->id,
        'collection' => 'cover',
        'disk' => 'public',
        'path' => "venue/{$venue->id}/cover/two.jpg",
        'mime_type' => 'image/jpeg',
        'size' => 100,
        'sort_order' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]))->toThrow(QueryException::class);
});

it('reorders a venue gallery', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $first = Media::factory()->for($venue, 'model')->collection(MediaCollection::GALLERY)->sortOrder(1)->create();
    $second = Media::factory()->for($venue, 'model')->collection(MediaCollection::GALLERY)->sortOrder(2)->create();

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/admin/venues/{$venue->id}/images/order", [
            'images' => [
                ['id' => $first->id, 'sort_order' => 2],
                ['id' => $second->id, 'sort_order' => 1],
            ],
        ])
        ->assertOk();

    expect($first->fresh()->sort_order)->toBe(2);
    expect($second->fresh()->sort_order)->toBe(1);
});

it('forbids a venue manager who does not manage the venue from uploading an image', function () {
    Storage::fake('public');
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(403);
});

it('forbids a customer from uploading a venue image', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(403);
});

it('rejects a non-image file upload', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/images", [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('rejects an image over the configured size limit', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/images", [
            'image' => UploadedFile::fake()->image('big.jpg')->size(config('media.max_kilobytes') + 100),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});
