# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 5 — Camera Eloquent model.

The implementation is complete locally and awaiting commit. Phase 6 (Clients and relationships) has not started.

## Current state

- Laravel 13.31 with the official Livewire Starter Kit;
- PHP 8.4 locally and PHP 8.5 in Sail;
- Livewire 4, Flux UI 2, Tailwind CSS 4, and Vite 8;
- PostgreSQL 18 as the application database;
- Sail services: Laravel, PostgreSQL, and optional local Adminer;
- Pest, Pint, and Larastan are installed;
- authentication, profile/settings pages, and the protected dashboard come from the Starter Kit;
- the `cameras` table migration has been applied;
- `Camera` model, boolean cast, factory, and a database feature test are implemented locally.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, Clients, or event journal exist yet.

## Current learning task

Review and commit the completed Camera model block and the documentation changes. After that, define the minimal Client domain before creating its schema or relationships.

## Last completed learning block

- Created the minimal `cameras` migration with `name`, `is_active`, and timestamps.
- Added the `Camera` Eloquent model.
- Configured mass assignment and a boolean cast for `is_active`.
- Added `CameraFactory`.
- Added a database feature test using `RefreshDatabase`.
- Verified the Camera test twice and ran targeted Pint successfully.

## What I learned

- A migration defines database structure; an Eloquent model represents rows in application code.
- `$fillable` controls mass assignment, while casts control PHP representations of stored values.
- Faker methods may return different types; factory values must match column types.
- `create()` returns the persisted model, and `findOrFail()` can reload a known row.
- `RefreshDatabase` isolates class-based database tests.
- A factory needs an explicit generic type for Larastan when using `HasFactory`.

## Decisions made

- The initial Camera schema stays deliberately minimal: `id`, `name`, `is_active`, and timestamps.
- `is_active` defaults to `true` in PostgreSQL and is cast to `boolean` by Eloquent.
- Use Laravel conventions and factories directly; no repository or service layer is needed for this model.
- Use PostgreSQL for database feature tests.
- Learning work is assigned in coherent blocks of 2–4 related tasks rather than mandatory single micro-tasks.
- Redis, queues, and external API abstractions remain postponed until a real use-case appears.

## Known baseline issues

These issues predate the Camera implementation:

- project-wide Pint reports formatting issues in six `lang/ru/*.php` files;
- Larastan reports that `ProfileValidationRules` is unused because its use is inside a Blade/Livewire component;
- Larastan reports a missing return in `UserFactory::withTwoFactor()`.

The Camera model, factory, and test have no current Pint or Larastan errors. The full `composer test` command is not green until the baseline issues are resolved.

## Open questions

Resolve only when needed:

- what a Client represents in MAX Messenger and which stable identifier it needs;
- deletion behavior for Camera ↔ Client assignments;
- Dahua webhook authentication contract;
- required event/rule fields;
- Dahua snapshot behavior;
- MAX API request workflow;
- which runtime settings belong in DB versus config/environment;
- production deployment target.

## Next likely steps

1. Commit the updated learning workflow separately from the Camera implementation.
2. Decide whether to repair the Starter Kit quality baseline before Phase 6.
3. Describe the Client domain and only then design its minimal migration.
4. Add the Camera ↔ Client relationship after both models have justified schemas.

## Last session handoff

Date: 2026-09-11.

Summary:

- Environment and project structure were inspected against the documentation.
- Phases 0–4 are complete.
- The Phase 5 Camera model/factory/test block passes its focused checks.
- Documentation was synchronized with the repository state.

Next action:

- Commit the current work, then begin Client domain discovery without adding Redis, queues, or integration code.
