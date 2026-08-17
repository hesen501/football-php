<?php

namespace App\Modules\Media\Providers;

use App\Modules\Field\Models\Field;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

/**
 * The Media module has no routes/policy of its own — image endpoints live
 * on each owning entity (see Venue/Field/Item/User's own Routes/api.php),
 * matching how a field has no ownership of its own either (see
 * FieldPolicy's docblock). This provider only wires up the migration and
 * the morph map below.
 */
class MediaModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Short aliases instead of storing fully-qualified class names in
        // `media.model_type` — decouples the table from these models' PHP
        // namespaces (this app has already reorganized modules once; see
        // git history) and keeps the column human-readable. Media is
        // currently the only polymorphic relation in the app; any future
        // one (e.g. review images/attachments) must add its models here too
        // — enforceMorphMap() rejects any morphable class that isn't listed.
        Relation::enforceMorphMap([
            'venue' => Venue::class,
            'field' => Field::class,
            'item' => Item::class,
            'user' => User::class,
        ]);
    }
}
