<?php

namespace App\Shared\Concerns;

use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Adds a polymorphic media relationship (see the `media` table / Media
 * model) to any model that can own uploaded images — currently Venue,
 * Field, Item, User. `media()` is every row regardless of collection; the
 * *Media() relations below each scope to one collection, so a Resource can
 * eager-load (and `whenLoaded()`) exactly the shape it needs — see
 * VenueResource's `cover_image`/`images`.
 *
 * Cleanup on the owner's own deletion is handled "manually" here rather
 * than by the database: model_id isn't a real FK (it's polymorphic — see
 * the media migration), so Postgres can't cascade this itself.
 * bootHasMedia() hooks whichever event means "this row is *actually* gone
 * for good" — `forceDeleted` for a soft-deleting model (a plain delete()
 * must NOT wipe media for a record that's still recoverable), or `deleted`
 * for one that isn't — and deletes each Media row individually (never a
 * bulk query) so Media's own `deleting` hook, which removes the physical
 * file, actually runs for every one of them.
 */
trait HasMedia
{
    public static function bootHasMedia(): void
    {
        $event = in_array(SoftDeletes::class, class_uses_recursive(static::class), true)
            ? 'forceDeleted'
            : 'deleted';

        static::$event(function (Model $model) {
            $model->media()->get()->each->delete();
        });
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->orderBy('sort_order');
    }

    public function coverMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', MediaCollection::COVER->value);
    }

    public function galleryMedia(): MorphMany
    {
        return $this->media()->where('collection', MediaCollection::GALLERY->value);
    }

    public function avatarMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', MediaCollection::AVATAR->value);
    }

    public function imageMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', MediaCollection::IMAGE->value);
    }
}
