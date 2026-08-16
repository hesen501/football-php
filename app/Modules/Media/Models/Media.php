<?php

namespace App\Modules\Media\Models;

use App\Modules\Media\Database\Factories\MediaFactory;
use App\Modules\Media\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * A single uploaded file owned by some other model (Venue, Field, Item,
 * User — see App\Shared\Concerns\HasMedia), grouped by `collection` (see
 * MediaCollection). Deliberately generic/reusable rather than one table per
 * owning entity — see the media migration's docblock for the schema
 * rationale.
 *
 * Never instantiated/deleted directly outside App\Modules\Media\Services\MediaService
 * — that's the only place collection invariants (singleton collections,
 * cover uniqueness) are enforced.
 */
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'model_type',
        'model_id',
        'collection',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'collection' => MediaCollection::class,
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }

    /**
     * The owning Venue/Field/Item/User. Named `model` (not e.g. `owner`) to
     * match the `model_type`/`model_id` column names — see the media
     * migration.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A public, disk-agnostic URL — local disk in dev, S3/CDN URL in
     * production, with zero call-site changes (see config/media.php).
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Deleting a Media row always deletes its physical file too — the one
     * invariant this system guarantees regardless of *why* the row is being
     * removed (explicit delete, replaced by a new singleton upload, or the
     * owner itself being force-deleted — see HasMedia::bootHasMedia()).
     * Callers must go through Eloquent deletes one row at a time (never
     * Media::query()->...->delete()) for this to fire — see MediaService.
     */
    protected static function booted(): void
    {
        static::deleting(function (Media $media) {
            Storage::disk($media->disk)->delete($media->path);
        });
    }
}
