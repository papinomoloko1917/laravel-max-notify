# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 7 — Authentication and admin shell is complete and committed.

Phase 8 — Camera management UI is in progress. The first read-only list and safe development-seeder slice is complete locally and awaiting commit.

## Current state

- Laravel 13.31 with the official Livewire Starter Kit;
- PHP 8.4 locally and PHP 8.5 in Sail;
- Livewire 4, Flux UI 2, Tailwind CSS 4, and Vite 8;
- PostgreSQL 18 as the application and test database;
- Sail services: Laravel, PostgreSQL, and optional local Adminer;
- Pest, Pint, and Larastan are installed;
- authentication, profile/settings pages, and the protected dashboard come from the Starter Kit;
- Camera, Client, their many-to-many relationship, and relationship tests are committed in `48844e8`;
- the Phase 6 commit has been pushed and local `main` currently matches `origin/main`;
- a protected `/cameras` Livewire page and named route `cameras.index` are committed in `48f8ba2`;
- the sidebar contains a Cameras link with a custom Flux-compatible `cctv` icon;
- feature tests cover guest and authenticated access, ordered camera rendering, active/inactive statuses, and the empty state;
- the Cameras page loads cameras ordered by name, renders a Flux table, distinguishes active/inactive cameras, and shows an empty state;
- Camera and User seeders provide manual development data only in the `local` environment;
- the predictable development user is repeatable through `updateOrCreate()` and has a verified email.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, or event journal exists yet.

## Current learning task

Review and commit/push the completed read-only Cameras list tests, local-only seeder safety, and documentation before moving to camera creation or editing.

## Completed work

- Created and tested the minimal Camera model.
- Created the `clients` table with unique `max_chat_id`.
- Added the Client model, integer cast, factory, and persistence test.
- Added a passing test that expects `QueryException` for duplicate `max_chat_id`.
- Created `camera_client` with foreign keys, cascading pivot cleanup, timestamps, and a composite unique constraint.
- Added reciprocal `BelongsToMany` methods to Camera and Client with Larastan generic annotations and `withTimestamps()`.
- Verified that one `attach()` writes the relationship once and the inverse relation reads the same pivot row.
- Added tests that reject duplicate Camera–Client pairs.
- Added tests proving that deleting either main model removes only its pivot rows and preserves the other main model.
- Added a protected Livewire Cameras page using the Starter Kit authentication middleware.
- Added guest/authenticated access tests for the Cameras page.
- Added the Cameras sidebar entry and a custom Flux-compatible CCTV icon.
- Removed the Starter Kit's demonstration Repository and Documentation sidebar links.
- Completed a read-only Camera list with ordering, active/inactive badges, and an empty state.
- Added focused tests that verify reverse-input sorting, status placement, and the empty state.
- Restricted User and Camera demonstration seeders to `local`.
- Made the predictable development user repeatable and email-verified.

## Current uncommitted changes

- `tests/Feature/CamerasTest.php` — focused ordered-list, status, and empty-state coverage;
- `database/seeders/UserSeeder.php` — local-only, repeatable, verified development user;
- `database/seeders/CameraSeeder.php` — local-only demonstration cameras;
- documentation synchronized by Codex with the completed test block.

## Verification at checkpoint

Checkpoint date: 2026-09-14.

- `artisan test tests/Feature/CamerasTest.php`: 4 tests pass with 8 assertions;
- `artisan test tests/Feature/Models`: 7 tests pass with 15 assertions;
- targeted Pint for the Cameras test, page, and seeders passes;
- `git diff --check` passes;
- Sail is running and all migrations are applied;
- `UserSeeder` succeeds on two consecutive local runs and produces a verified `test@mail.ru` user;
- Larastan still has only the two known baseline issues listed below.

## Next exact steps

1. Review and commit the Cameras list test, seeder safety changes, AGENTS rule, and documentation as logical commits.
2. Push `main` so this checkpoint is available on all development machines.
3. Begin the next Phase 8 block: camera creation, introduced through validation and a focused Livewire feature test.

## Decisions made

- Camera ↔ Client is many-to-many through conventional table `camera_client`.
- The same Camera–Client pair must be unique.
- Deleting either main record removes its pivot rows, not the other main record.
- Pivot timestamps record when an assignment is created or updated and are populated through `withTimestamps()` on both relationships.
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until confirmed against the MAX API contract.
- The Cameras page is an authenticated Livewire administration page; webhook traffic will remain outside Livewire.
- The first Camera UI slice is deliberately read-only; CRUD will be introduced incrementally.
- Starter Kit Repository and Documentation links were removed because they were template examples, not application navigation.
- Codex maintains checkpoint, roadmap, and architecture documentation after meaningful project steps; the learner continues to implement application code and configuration.
- Demonstration User and Camera seeders run only in `local`; the fixed local user is updated or created and marked email-verified.
- Redis, queues, and integration abstractions remain postponed.

## Known baseline issues

These issues predate the current relationship work:

- project-wide Pint reports formatting issues in six `lang/ru/*.php` files;
- Larastan reports that `ProfileValidationRules` is unused because its use is inside a Blade/Livewire component;
- Larastan reports a missing return in `UserFactory::withTwoFactor()`.

## Last session handoff

Date: 2026-09-14.

Summary:

- Phase 6 is committed and pushed in `48844e8`.
- Phase 7 and the WIP Cameras UI commit `48f8ba2` are present on `main` and `origin/main`.
- The read-only Cameras table now has focused ordering, status, and empty-state coverage.
- Cameras tests pass with 4 tests and 8 assertions; targeted Pint passes.
- The test update and this documentation synchronization are currently uncommitted.
- User and Camera seeders are local-only; the predictable development user is repeatable and verified.

Next action on another machine:

- Commit/push the completed read-only Cameras and seeder-safety slice, then start camera creation.
