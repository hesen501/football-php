# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A REST API (no server-rendered frontend — see "API-only" below) for a football venue/field booking platform: venue discovery, availability, bookings, and a 10% platform commission on customer-app bookings. PHP 8.3+, Laravel 13, PostgreSQL, Sanctum bearer-token auth, `spatie/laravel-permission`, Pest, `dedoc/scramble` for live OpenAPI docs.

The **frontend is a separate React project**, not in this repo: `~/Downloads/football-booking-frontend` (see `.claude/settings.local.json` for the paths already permitted). Don't go looking for frontend code here.

## Commands

```bash
# Setup (Postgres, not SQLite — see "Why Postgres" below)
composer install
cp .env.example .env && php artisan key:generate
createuser -P football_booking && createdb -O football_booking football_booking
createdb -O football_booking football_booking_test   # separate DB used only by the test suite
php artisan migrate:fresh --seed                      # see "Seeding" below
php artisan storage:link                               # needed once for local media URLs to resolve

# Tests
composer test                                          # = artisan config:clear && artisan test
php artisan test --filter="lets a super admin create a venue"   # single test by name
php artisan test tests/Feature/Admin/Venues/CreateVenueTest.php  # single file
./vendor/bin/pest tests/Unit/Booking/BookingPricingTest.php       # pest directly also works

# Code style (no phpstan/larastan configured — Pint is the only static check)
./vendor/bin/pint --test     # check only
./vendor/bin/pint --dirty    # fix only files changed vs. the last commit

# OpenAPI docs — regenerate/inspect without a running server
php artisan scramble:export --path=/tmp/api.json
# Served live (needs `artisan serve`) at /docs/api (UI) and /docs/api.json (raw spec)

php artisan route:list                                 # full route table
php artisan migrate:fresh --seed                        # reset dev DB with full demo data
```

Seeded admin login (dev-only, `UserSeeder`): `admin@footballbooking.test` / `password`. Full credential list is in that seeder's docblock.

## Architecture

**Modular monolith.** Each domain lives under `app/Modules/{Auth,User,Venue,Field,Item,Booking,Media,Dashboard}/`, owning its own `Models/`, `Enums/`, `Policies/`, `Services/`, `Http/{Controllers,Requests,Resources}/`, `Database/{Migrations,Factories,Seeders}/`, `Routes/api.php`, and a `Providers/{Module}ModuleServiceProvider.php` that calls `loadMigrationsFrom()`/`loadRoutesFrom()`/`Gate::policy()`. Providers are registered explicitly in `bootstrap/providers.php` — `routes/api.php` itself is intentionally empty (just a comment) since every real route is loaded by its owning module's provider. `app/Shared/` holds cross-cutting infra used by every module: the `{message, errors?, error_code?}` JSON error envelope (`Shared/Exceptions/ApiException` + subclasses self-render via `->render()`, no exception-handler registration needed), `ApiResponse`, list-endpoint search/sort/pagination (`Shared/Http/Filtering`), the admin-vs-customer route guard middleware, and the `HasMedia` concern (see Media below).

**Two API surfaces, one set of domain modules.** `Http/Controllers/Admin/*` (prefix `/api/admin`, behind `auth:sanctum` + `admin.panel` middleware = SUPER_ADMIN or VENUE_MANAGER only) vs `Http/Controllers/Customer/*` (prefix `/api`, either public — venue/field/item browsing, availability — or behind plain `auth:sanctum`). Distinct login endpoints: `/api/admin/auth/login` (rejects CUSTOMER-only accounts) vs `/api/auth/login` (any active account). This is API-only end to end: `routes/web.php`, the default Blade view, and the Vite/Tailwind scaffold have been removed; `bootstrap/app.php`'s `withRouting()` has no `web:` entry.

**Roles.** `spatie/laravel-permission`, backed by `App\Modules\User\Enums\UserRole` (`SUPER_ADMIN`, `VENUE_MANAGER`, `CUSTOMER`). Always pass the enum case to `assignRole()`/`hasRole()`/`hasAnyRole()`/`Role::findOrCreate()`/the `role()` query scope — this version of spatie/laravel-permission accepts a `BackedEnum` natively, so there's no reason to fall back to raw strings anywhere. `Gate::before` (in `AuthModuleServiceProvider`) grants SUPER_ADMIN every ability unconditionally; every Policy method only needs to handle the non-super-admin cases. VENUE_MANAGER is scoped per-venue via the `venue_managers` pivot (`Venue::isManagedBy()`) — most VENUE_MANAGER policy checks are "has the permission AND manages this specific venue/field".

**Booking financials are snapshotted, never recomputed.** `bookings.hourly_price/total_price/commission_rate/commission_amount/venue_amount` are frozen at creation time (`BookingService::calculatePrice()`); changing a field's price or the platform commission rate (`platform_settings` table, `PlatformSetting::getCommissionRate()`) never rewrites historical bookings. Commission (default 10%, `config('booking.commission_rate')` as fallback) applies only to `BookingSource::CUSTOMER_APP`; `ADMIN_PANEL` bookings carry zero commission. Overlap prevention is two-layered: a Postgres advisory lock per field (fast, friendly `BookingSlotUnavailableException`) plus a DB-level `EXCLUDE USING gist` constraint on `(field_id, tsrange(start_time, end_time))` for non-cancelled bookings (needs the `btree_gist` extension — see the bookings migration) as the actual correctness guarantee under concurrency. Bookable hours are further constrained by `venue_working_hours` (one row per venue per day-of-week); a venue with **zero** working-hours rows is treated as unrestricted, for backward compatibility with venues written directly via factory/seeder rather than through `VenueService::create()` (which always seeds all 7 days).

**No separate Payment/Review/Notification tables.** Payment status/reference/provider live directly as columns on `bookings` — don't go looking for a `payments` table. Reviews and notifications don't exist in this codebase at all.

**Media/images (`app/Modules/Media`).** One polymorphic `media` table shared by Venue/Field/Item/User (no per-entity `*_url` columns). `model_type` stores a short morph-map alias (`venue`/`field`/`item`/`user`, not the FQCN — enforced in `MediaModuleServiceProvider`, which must boot before anything queries the relation). `MediaCollection` enum: `avatar`/`cover`/`image` are DB-enforced singletons per owner (a partial unique index on `(model_type, model_id, collection)`), `gallery` is multi-row with `sort_order`. `App\Shared\Concerns\HasMedia` (added to all four owning models) provides the relations and hooks each model's actual-removal event (`forceDeleted` for these soft-deleting models, not `deleted`) to clean up media rows — deleted one at a time via `->each->delete()`, never a bulk query, so each `Media` row's own `deleting` hook (which removes the physical file) fires. `App\Modules\Media\Services\MediaService` is the *only* place upload/delete/setCover/reorder logic lives — every domain's image controller (`Venue/Field/Item/User`'s own `*ImageController`) injects it rather than duplicating upload logic; domain authorization stays in each domain's own Policy/FormRequest. Files go on `config('media.disk')` (defaults to the `public` disk — deliberately different from `FILESYSTEM_DISK`, which is the app's private-by-default disk), swappable to S3 via env with no code changes.

**Seeding is two-tiered.** `DatabaseSeeder` always runs roles/permissions + the one `SUPER_ADMIN` account + the commission-rate platform setting — this is the minimum the test suite needs, since `tests/TestCase.php` sets `$seed = true` and `RefreshDatabase` re-seeds through the same class every run. Everything else (10 named venues with working hours, fields, item catalog, ~90 interconnected bookings across every status/source, placeholder images generated on the fly with GD) is real demo data gated behind `! app()->environment('testing')`, so the test suite never pays for it. `UserSeeder` follows the same split internally. When adding a new demo-data seeder, wire it into the second (non-testing) block in `DatabaseSeeder`, not the first.

**Why Postgres, not SQLite, for tests.** The booking-overlap `EXCLUDE` constraint and advisory locks are Postgres-specific; `phpunit.xml` points the test run at a real `football_booking_test` database rather than an in-memory/SQLite one so that concurrency-correctness is actually exercised.

**API docs need no manual upkeep.** `dedoc/scramble` introspects routes, FormRequest validation rules, and API Resources to generate the OpenAPI spec live — there are no docblock annotations anywhere in the codebase and none should be added. A FormRequest with an `image`/`file` rule is automatically documented as `multipart/form-data`.

## Subagents

`.claude/agents/{explorer,architect,backend,database,tester,reviewer}.md` — task-specific subagents scoped to this repo (explorer/architect/database are read-only; backend implements; tester adds tests; reviewer does a final critical pass). Prefer delegating to the matching one over doing exploration/review inline when a task calls for it.
