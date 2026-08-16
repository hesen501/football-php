<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | Deliberately separate from FILESYSTEM_DISK (config/filesystems.php's
    | app-wide default, currently "local" — a *private* disk under
    | storage/app/private, see that config file). Uploaded venue/field/item/
    | avatar images need to be publicly viewable, so they default to the
    | "public" disk instead. Point this at "s3" (already configured in
    | config/filesystems.php) in production for object storage — nothing
    | else in the media system needs to change.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | Applied by App\Shared\Rules\ImageUploadRules, used by every image
    | upload FormRequest (Store{Venue,Field,Item}ImageRequest, StoreAvatarRequest).
    | 'max_kilobytes' matches Laravel's `max:` validation rule, which is
    | already expressed in kilobytes.
    |
    */

    'max_kilobytes' => (int) env('MEDIA_MAX_KILOBYTES', 5120), // 5 MB

    'max_width' => (int) env('MEDIA_MAX_WIDTH', 8000),

    'max_height' => (int) env('MEDIA_MAX_HEIGHT', 8000),

    /** @var array<int, string> */
    'mimes' => explode(',', env('MEDIA_ALLOWED_MIMES', 'jpg,jpeg,png,webp')),

];
