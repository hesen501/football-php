# Football Field Booking Platform — API

A REST API for a football venue/field booking platform: venue discovery, availability, and bookings, with a 10% platform commission on customer-app bookings. Built as a modular monolith — see [Architecture](#architecture) below.

## Stack

- PHP 8.3+, Laravel 13
- PostgreSQL (uses a `btree_gist` **EXCLUDE constraint** for concurrency-safe booking overlap prevention — see `app/Modules/Booking/Database/Migrations/`)
- Laravel Sanctum (token auth), `spatie/laravel-permission` (roles/permissions)
- Pest (tests), `dedoc/scramble` (live OpenAPI docs, no manual annotation upkeep)

## Setup

```bash
composer install

cp .env.example .env
php artisan key:generate

# Create the Postgres role/database (adjust to your local Postgres setup):
createuser -P football_booking          # password: football_booking
createdb -O football_booking football_booking
createdb -O football_booking football_booking_test   # used by the test suite

php artisan migrate --seed
php artisan serve
```

Seeded dev admin login (dev-only — see `AdminUserSeeder`): `admin@footballbooking.test` / `password`.

## Testing

```bash
composer test          # or: ./vendor/bin/pest
./vendor/bin/pint       # code style
```

Tests run against the real `football_booking_test` Postgres database (not SQLite) — the booking-overlap/concurrency guarantees are Postgres-specific and wouldn't be meaningfully testable otherwise.

## API docs

Interactive OpenAPI docs, generated live from routes/FormRequests/Resources (nothing to keep in sync by hand): `http://localhost:8000/docs/api`. Raw spec at `/docs/api.json` — importable directly into Postman via *Import → Link*.

## Architecture

Modular monolith — domain modules under `app/Modules/{Auth,User,Venue,Field,Booking,Dashboard}`, each owning its own models, migrations, factories, seeders, routes, and a `ServiceProvider` that wires them in (registered in `bootstrap/providers.php`). Cross-cutting infrastructure (exception → JSON envelope, pagination/sort/search helpers, the admin-vs-customer surface guard) lives in `app/Shared/`.

Two audiences share the domain modules but have separate controllers/routes: `Http/Controllers/Admin/*` (SUPER_ADMIN / VENUE_MANAGER, behind the `admin.panel` middleware) and `Http/Controllers/Customer/*` (any authenticated user, or public where noted — venue/field browsing and availability need no auth at all).

**Roles:** `SUPER_ADMIN` (bypasses all authorization checks), `VENUE_MANAGER` (scoped to venues they co-manage), `CUSTOMER` — a user can hold more than one role.

**Booking commission:** 10% (configurable, `platform_settings` table) on `CUSTOMER_APP`-sourced bookings only; `ADMIN_PANEL` bookings carry no commission. Every booking snapshots its own `hourly_price`/`total_price`/`commission_rate`/`commission_amount`/`venue_amount` at creation time — changing the field's price or the platform rate later never rewrites historical bookings.
# football-php
