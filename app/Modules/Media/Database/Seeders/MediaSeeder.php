<?php

namespace App\Modules\Media\Database\Seeders;

use App\Modules\Field\Models\Field;
use App\Modules\Item\Models\Item;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Dev/demo data — see DatabaseSeeder for why this only runs outside
 * `testing`. Attaches placeholder images to every Venue (1 cover + 2
 * gallery), Field (1 cover + 1 gallery), Item (1 image) and User (1
 * avatar) created by the seeders that ran before this one.
 *
 * Images are generated on the fly with GD via UploadedFile::fake()->image()
 * — the same helper Http feature tests use to fake an upload — rather than
 * depending on external image URLs (which can disappear) or checking
 * binary fixtures into the repo. Each one is a real, valid, tiny JPEG, and
 * every call goes through MediaService::upload() exactly like a real HTTP
 * upload would, so this exercises the same singleton-replace/gallery-
 * sort_order logic it's seeding data for — no separate/duplicated seeding
 * code path.
 */
class MediaSeeder extends Seeder
{
    public function __construct(private readonly MediaService $media) {}

    public function run(): void
    {
        if (Media::query()->exists()) {
            $this->command?->info('Media already exists — skipping MediaSeeder.');

            return;
        }

        Venue::query()->each(function (Venue $venue) {
            $this->media->upload($venue, $this->fakeImage("venue-{$venue->id}-cover.jpg"), MediaCollection::COVER);
            $this->media->upload($venue, $this->fakeImage("venue-{$venue->id}-gallery-1.jpg"), MediaCollection::GALLERY);
            $this->media->upload($venue, $this->fakeImage("venue-{$venue->id}-gallery-2.jpg"), MediaCollection::GALLERY);
        });

        Field::query()->each(function (Field $field) {
            $this->media->upload($field, $this->fakeImage("field-{$field->id}-cover.jpg"), MediaCollection::COVER);
            $this->media->upload($field, $this->fakeImage("field-{$field->id}-gallery-1.jpg"), MediaCollection::GALLERY);
        });

        Item::query()->each(function (Item $item) {
            $this->media->upload($item, $this->fakeImage("item-{$item->id}.jpg"), MediaCollection::IMAGE);
        });

        User::query()->each(function (User $user) {
            $this->media->upload($user, $this->fakeImage("user-{$user->id}-avatar.jpg"), MediaCollection::AVATAR);
        });

        $this->command?->info('MediaSeeder: attached placeholder images to venues, fields, items and users.');
    }

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->image($name, 640, 480);
    }
}
