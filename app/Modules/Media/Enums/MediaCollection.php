<?php

namespace App\Modules\Media\Enums;

/**
 * The named "buckets" a piece of media can belong to for its owning model.
 * GALLERY is the only multi-row collection — AVATAR/COVER/IMAGE are
 * enforced as at-most-one-per-owner both here (isSingleton()) and at the DB
 * level (see the `media_singleton_collection_unique` partial index in the
 * media migration).
 */
enum MediaCollection: string
{
    /** User's single profile photo. */
    case AVATAR = 'avatar';

    /** Venue/Field's single "main" photo — always also present in the owner's gallery-wide `media()` relation. */
    case COVER = 'cover';

    /** Venue/Field's multiple, orderable photos (including whichever one is currently the cover). */
    case GALLERY = 'gallery';

    /** Item's single product photo. */
    case IMAGE = 'image';

    /**
     * Whether an owner can have at most one Media row in this collection.
     * MediaService::upload() uses this to decide whether uploading again
     * replaces the existing row (see the class docblock there) or simply
     * adds another gallery entry.
     */
    public function isSingleton(): bool
    {
        return $this !== self::GALLERY;
    }
}
