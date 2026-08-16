<?php

namespace App\Modules\Media\Database\Factories;

use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 *
 * Doesn't touch the filesystem — `path` just points at a plausible-looking
 * location under the configured media disk. Fine for anything that only
 * needs the DB row (resource/relation tests, seeders); tests that exercise
 * upload/delete/replace go through MediaService with Storage::fake()
 * instead (see the "Images" feature tests under each module).
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'png', 'webp']);

        return [
            // model_type/model_id are left unset here — always provide an
            // owner via for($owner, 'model'), e.g. Media::factory()->for($venue, 'model').
            'collection' => MediaCollection::GALLERY,
            'disk' => config('media.disk'),
            'path' => 'media/fixtures/'.Str::uuid().'.'.$extension,
            'original_filename' => 'photo.'.$extension,
            'mime_type' => match ($extension) {
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
            },
            'size' => fake()->numberBetween(20_000, 800_000),
            'sort_order' => 0,
        ];
    }

    public function collection(MediaCollection $collection): static
    {
        return $this->state(fn () => ['collection' => $collection]);
    }

    public function sortOrder(int $sortOrder): static
    {
        return $this->state(fn () => ['sort_order' => $sortOrder]);
    }
}
