<?php

namespace App\Shared\Rules;

use Illuminate\Validation\Rule;

/**
 * The one place image-upload validation is defined, shared by every
 * Store{Venue,Field,Item}ImageRequest/StoreAvatarRequest — see
 * config/media.php for the actual limits, which are configurable per
 * deployment via env vars without touching any of these request classes.
 *
 * `image` (on top of `file`) already validates the upload actually decodes
 * as an image (via getimagesize()), not just that its extension/declared
 * MIME type looks right; `mimes` cross-checks the *sniffed* MIME type
 * against the allow-list, so a renamed .php-with-a-.jpg-extension is
 * rejected on content, not on trusting the client-supplied extension.
 */
class ImageUploadRules
{
    /** @return array<int, mixed> */
    public static function forField(string $field): array
    {
        return [
            $field => array_merge(
                ['required', 'file', 'image'],
                self::sharedRules(),
            ),
        ];
    }

    /** @return array<int, mixed> */
    private static function sharedRules(): array
    {
        return [
            'mimes:'.implode(',', config('media.mimes')),
            'max:'.config('media.max_kilobytes'),
            Rule::dimensions()
                ->maxWidth(config('media.max_width'))
                ->maxHeight(config('media.max_height')),
        ];
    }
}
