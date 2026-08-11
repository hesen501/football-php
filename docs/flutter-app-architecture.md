# Flutter Customer App — Architecture Plan

Planning notes for a Flutter client consuming this API's **customer** surface only
(admin/venue-manager stays out of scope — web panel or a separate client, decided later).

## Where it lives

Keep it as a **separate project**, sibling to this repo (e.g. `../football-booking-app/`),
not nested inside the Laravel tree. Different toolchains, different release cadence —
don't couple their lifecycles.

## Endpoints the app needs to cover

From `routes/api.php` → each module's `Routes/api.php`, the customer surface is exactly:

| Domain | Endpoints |
|---|---|
| Auth | `POST /auth/register`, `login`, `forgot-password`, `reset-password`, `logout`, `GET /auth/me`, `POST /auth/email/resend`, `GET /auth/email/verify/{id}/{hash}` (deep link) |
| Venues | `GET /venues`, `GET /venues/{venue}` (public) |
| Fields | `GET /venues/{venue}/fields`, `GET /fields/{field}`, `GET /fields/{field}/availability` (public) |
| Bookings | `GET /bookings`, `POST /bookings`, `GET /bookings/{booking}`, `POST /bookings/{booking}/cancel` (auth required) |
| Profile | `GET /profile`, `PUT/PATCH /profile` (auth required) |

Five feature modules, mirroring the backend's `Modules/{Auth,Venue,Field,Booking,User}`
split almost 1:1 — that symmetry is worth preserving in the client so a backend change
is easy to trace to a client change.

Auth is Sanctum **bearer-token** based (not cookie/CSRF SPA auth), which is the simple
case for a mobile client: no cookie jar or CSRF dance, just an `Authorization: Bearer …`
header attached per request.

## Recommended stack

- **State management:** Riverpod (`flutter_riverpod` + `riverpod_generator`) — testable,
  no BuildContext plumbing, scales cleanly per-feature.
- **Networking:** `dio`, with an interceptor that attaches the Sanctum bearer token and
  maps the backend's JSON error envelope (`app/Shared/Exceptions` → consistent
  `{message, errors}` shape) to a typed `ApiException`.
- **Models:** `freezed` + `json_serializable`. Since the backend already exposes a live
  OpenAPI spec at `/docs/api.json` (dedoc/scramble), it's worth trying
  `openapi-generator` (dart-dio client) to generate request/response models directly
  from that spec instead of hand-writing them — re-run on backend changes instead of
  manually chasing drift.
- **Auth token storage:** `flutter_secure_storage`.
- **Routing:** `go_router`, with a redirect guard on the auth state for the
  booking/profile routes.
- **Env config:** `--dart-define` (or `flutter_dotenv`) for `API_BASE_URL`, mirroring
  the backend's `.env` pattern (dev/staging/prod).
- **Testing:** `mocktail` + Riverpod's `ProviderContainer` for unit tests,
  `integration_test` for the booking flow end-to-end.

## Folder structure

```
lib/
├── main.dart
├── app.dart                     # MaterialApp.router, theme, go_router setup
├── core/
│   ├── network/
│   │   ├── api_client.dart      # Dio instance + interceptors
│   │   └── api_exception.dart   # maps backend error envelope
│   ├── storage/
│   │   └── token_storage.dart   # flutter_secure_storage wrapper
│   ├── router/
│   │   └── app_router.dart      # go_router + auth redirect guard
│   └── theme/
├── features/
│   ├── auth/
│   │   ├── data/                # AuthRepository, DTOs (or generated models)
│   │   ├── domain/               # entities, if you separate from DTOs
│   │   ├── application/          # Riverpod providers/notifiers (login, register, session)
│   │   └── presentation/         # screens: login, register, forgot-password, verify-email
│   ├── venues/
│   │   ├── data/
│   │   ├── application/
│   │   └── presentation/         # venue list, venue detail
│   ├── fields/
│   │   ├── data/
│   │   ├── application/
│   │   └── presentation/         # field detail, availability calendar/slots
│   ├── bookings/
│   │   ├── data/
│   │   ├── application/
│   │   └── presentation/         # booking list, create/confirm, detail, cancel
│   └── profile/
│       ├── data/
│       ├── application/
│       └── presentation/
└── shared/
    ├── widgets/                  # buttons, loading/error states, empty states
    └── utils/
```

Rationale for the shape:

- **`core/`** is the cross-cutting layer — the client-side equivalent of the backend's
  `app/Shared/`.
- Each **feature** stays self-contained (data/application/presentation) so a module can
  be built, tested, and reasoned about in isolation, same motivation as the backend's
  per-module `ServiceProvider`.
- **`fields/availability`** and **`bookings/create`** are the two screens with real
  complexity (slot picking, overlap-safe booking submission, commission-aware price
  display) — budget the most design/state time there.
- Skip `user` (admin) and `dashboard` modules entirely — out of scope for a customer app.

## First commands, when scaffolding starts

```bash
flutter create --org com.footballbooking football_booking_app
cd football_booking_app
flutter pub add dio flutter_riverpod riverpod_annotation go_router flutter_secure_storage freezed_annotation json_annotation
flutter pub add -d riverpod_generator build_runner freezed json_serializable mocktail
```
