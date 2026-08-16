<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The single place every image upload/delete/cover/reorder operation goes
 * through, regardless of which entity (Venue, Field, Item, User) owns the
 * media — this is what Venue/Field/Item/User image controllers all inject
 * instead of duplicating upload/replace/cleanup logic four times. Domain
 * authorization (can *this* user touch *this* venue's images?) stays in
 * each domain's own Policy/FormRequest, same as everywhere else in the app
 * — this service only knows about media, never about who's allowed to ask
 * for it.
 */
class MediaService
{
    /**
     * Stores $file under the owner's collection folder and creates its
     * Media row. For a singleton collection (AVATAR/COVER/IMAGE — see
     * MediaCollection::isSingleton()), any existing row in that collection
     * is deleted first — DB row and physical file both (see
     * deleteAllInCollection()) — so "upload" doubles as "replace" for
     * those, per the avatar/item-image endpoints' documented semantics.
     * GALLERY uploads never replace anything; each call adds one more row,
     * appended to the end via sort_order.
     */
    public function upload(Model $owner, UploadedFile $file, MediaCollection $collection): Media
    {
        return DB::transaction(function () use ($owner, $file, $collection) {
            if ($collection->isSingleton()) {
                $this->deleteAllInCollection($owner, $collection);
            }

            $disk = config('media.disk');
            $path = $file->store($this->folderFor($owner, $collection), $disk);

            $sortOrder = $collection === MediaCollection::GALLERY
                ? (int) $this->collectionQuery($owner, $collection)->max('sort_order') + 1
                : 0;

            return $owner->media()->create([
                'collection' => $collection,
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'sort_order' => $sortOrder,
            ]);
        });
    }

    /**
     * Deletes every Media row $owner has in $collection, one at a time
     * (never a bulk query) so each row's own `deleting` hook — which
     * removes the physical file, see the Media model — actually fires.
     * The main entry point for the item-image/avatar "delete" endpoints,
     * and how upload() clears the way before replacing a singleton.
     */
    public function deleteAllInCollection(Model $owner, MediaCollection $collection): void
    {
        $this->collectionQuery($owner, $collection)->get()->each->delete();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    /**
     * Promotes $media to COVER, demoting whichever media was previously the
     * cover (if any) back to GALLERY — "old cover -> gallery, new cover ->
     * cover", done inside one transaction so an owner is never briefly
     * without a cover or with two at once.
     */
    public function setCover(Model $owner, Media $media): Media
    {
        $this->assertBelongsTo($media, $owner);

        return DB::transaction(function () use ($owner, $media) {
            $owner->media()
                ->where('collection', MediaCollection::COVER->value)
                ->whereKeyNot($media->getKey())
                ->update(['collection' => MediaCollection::GALLERY->value]);

            $media->update(['collection' => MediaCollection::COVER->value]);

            return $media->fresh();
        });
    }

    /**
     * Bulk sort_order update for $owner's gallery. Every id in $order is
     * validated (by the calling FormRequest — see e.g.
     * ReorderVenueImagesRequest) to already belong to $owner before this
     * runs, so this never needs to re-check ownership row by row.
     *
     * @param  array<int, array{id: int, sort_order: int}>  $order
     */
    public function reorder(Model $owner, array $order): void
    {
        DB::transaction(function () use ($owner, $order) {
            foreach ($order as $row) {
                $owner->media()->whereKey($row['id'])->update(['sort_order' => $row['sort_order']]);
            }
        });
    }

    /**
     * Guards every {media} route-model-bound action (destroy/setCover)
     * against a caller passing a real Media id that belongs to some *other*
     * owner — without this, `DELETE /venues/1/images/{media}` would happily
     * delete a media row that actually belongs to venue 2. Renders as a
     * plain 404 (same as the media id not existing at all), not a 403 —
     * deliberately not confirming to the caller that the id exists elsewhere.
     */
    public function assertBelongsTo(Media $media, Model $owner): void
    {
        if ($media->model_type !== $owner->getMorphClass() || (int) $media->model_id !== (int) $owner->getKey()) {
            throw (new ModelNotFoundException)->setModel(Media::class, [$media->getKey()]);
        }
    }

    private function collectionQuery(Model $owner, MediaCollection $collection)
    {
        return $owner->media()->where('collection', $collection->value);
    }

    private function folderFor(Model $owner, MediaCollection $collection): string
    {
        return sprintf('%s/%d/%s', $owner->getMorphClass(), $owner->getKey(), $collection->value);
    }
}
