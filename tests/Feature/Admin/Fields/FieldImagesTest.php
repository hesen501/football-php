<?php

use App\Modules\Field\Models\Field;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets a super admin upload a field image into the gallery', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/admin/fields/{$field->id}/images", [
        'image' => UploadedFile::fake()->image('photo.jpg', 300, 300),
    ]);

    $response->assertCreated()->assertJsonPath('data.collection', 'gallery');

    $media = Media::findOrFail($response->json('data.id'));
    expect($media->model_type)->toBe($field->getMorphClass());
    expect($media->model_id)->toBe($field->id);
    Storage::disk('public')->assertExists($media->path);
});

it('deletes a field image and removes the physical file', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();

    $uploaded = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/fields/{$field->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->json('data');

    $media = Media::findOrFail($uploaded['id']);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/fields/{$field->id}/images/{$media->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($media->path);
});

it('sets a field image as the cover, demoting the previous cover back to the gallery', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();
    $oldCover = Media::factory()->for($field, 'model')->collection(MediaCollection::COVER)->create();
    $newCover = Media::factory()->for($field, 'model')->collection(MediaCollection::GALLERY)->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/fields/{$field->id}/images/{$newCover->id}/cover")
        ->assertOk()
        ->assertJsonPath('data.collection', 'cover');

    expect($oldCover->fresh()->collection)->toBe(MediaCollection::GALLERY);
    expect($newCover->fresh()->collection)->toBe(MediaCollection::COVER);
});

it('forbids a venue manager who does not manage the field\'s venue from uploading an image', function () {
    Storage::fake('public');
    $manager = User::factory()->venueManager()->create();
    $field = Field::factory()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/fields/{$field->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(403);
});

it('lets a venue manager who manages the field\'s venue upload an image', function () {
    Storage::fake('public');
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $field = Field::factory()->create(['venue_id' => $venue->id]);

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/fields/{$field->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertCreated();
});

it('forbids a customer from uploading a field image', function () {
    Storage::fake('public');
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/admin/fields/{$field->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(403);
});

it('rejects a non-image file upload for a field', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/fields/{$field->id}/images", [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('requires an image to upload a field image', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/fields/{$field->id}/images", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});
