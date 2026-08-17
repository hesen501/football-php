<?php

use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->media = new MediaService;
});

it('deletes the physical file when a media row is deleted', function () {
    $venue = Venue::factory()->create();
    $media = $this->media->upload($venue, UploadedFile::fake()->image('photo.jpg'), MediaCollection::GALLERY);
    $path = $media->path;

    Storage::disk('public')->assertExists($path);

    $media->delete();

    Storage::disk('public')->assertMissing($path);
});

it('does not delete media or files when the owner is only soft-deleted', function () {
    $venue = Venue::factory()->create();
    $media = $this->media->upload($venue, UploadedFile::fake()->image('photo.jpg'), MediaCollection::GALLERY);

    $venue->delete(); // soft delete — still recoverable

    expect(Media::find($media->id))->not->toBeNull();
    Storage::disk('public')->assertExists($media->path);
});

it('deletes every media row and physical file when the owner is force-deleted', function () {
    $venue = Venue::factory()->create();
    $cover = $this->media->upload($venue, UploadedFile::fake()->image('cover.jpg'), MediaCollection::COVER);
    $gallery = $this->media->upload($venue, UploadedFile::fake()->image('gallery.jpg'), MediaCollection::GALLERY);

    $venue->forceDelete();

    expect(Media::find($cover->id))->toBeNull();
    expect(Media::find($gallery->id))->toBeNull();
    Storage::disk('public')->assertMissing($cover->path);
    Storage::disk('public')->assertMissing($gallery->path);
});

it('replacing a singleton-collection upload deletes the previous file, not just the row', function () {
    $user = User::factory()->customer()->create();

    $first = $this->media->upload($user, UploadedFile::fake()->image('one.jpg'), MediaCollection::AVATAR);
    $firstPath = $first->path;

    $second = $this->media->upload($user, UploadedFile::fake()->image('two.jpg'), MediaCollection::AVATAR);

    expect(Media::find($first->id))->toBeNull();
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($second->path);
    expect($user->media()->where('collection', MediaCollection::AVATAR->value)->count())->toBe(1);
});
