# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 6 — Clients and relationships, implemented locally and ready to commit.

Phase 5 (Camera Eloquent model) and the initial Client model are committed. The Camera ↔ Client many-to-many relationship is complete locally. Phase 7 (Authentication and admin shell) has not started.

## Current state

- Laravel 13.31 with the official Livewire Starter Kit;
- PHP 8.4 locally and PHP 8.5 in Sail;
- Livewire 4, Flux UI 2, Tailwind CSS 4, and Vite 8;
- PostgreSQL 18 as the application and test database;
- Sail services: Laravel, PostgreSQL, and optional local Adminer;
- Pest, Pint, and Larastan are installed;
- authentication, profile/settings pages, and the protected dashboard come from the Starter Kit;
- Camera migration, model, cast, factory, and focused feature test are committed;
- Client migration, model, factory, and initial feature test are committed in `f56a2c6`;
- the `camera_client` pivot migration has been applied locally as batch 4;
- Camera and Client expose reciprocal `BelongsToMany` relationships;
- pivot timestamps are populated through `withTimestamps()`;
- relationship, duplicate-pair, and cascading-deletion behavior is covered by feature tests.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, or event journal exists yet.

## Current learning task

Review and commit the completed Phase 6 relationship block and these documentation updates. After that, begin Phase 7 by inspecting the existing Starter Kit authentication and protected admin shell before adding new UI behavior.

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

## Current uncommitted changes

- `app/Models/Camera.php` — completed `clients()` relationship;
- `app/Models/Client.php` — completed `cameras()` relationship;
- `tests/Feature/Models/CameraTest.php` — duplicate-pair and Camera cascade tests;
- `tests/Feature/Models/ClientTest.php` — bidirectional relationship and Client cascade tests;
- documentation updates marking Phase 6 complete locally.

## Verification at checkpoint

Run on 2026-09-13:

- `artisan migrate:status`: pivot migration is `Ran` as batch 4 in the current local database;
- `artisan test tests/Feature/Models`: 7 tests pass with 15 assertions;
- full `artisan test --compact`: 31 tests pass, with 1 existing skipped/risky test and 68 assertions;
- targeted Pint for the changed models and tests: passes;
- Larastan: only the two existing baseline errors remain; Camera and Client introduce no new errors;
- `git diff --check`: passes.

## Next exact steps

1. Review and commit the completed Phase 6 implementation and documentation.
2. Inspect the existing Starter Kit authentication, middleware, dashboard route, and layout as the start of Phase 7.
3. Decide the smallest authenticated admin-shell change before implementing Camera management UI.

## Decisions made

- Camera ↔ Client is many-to-many through conventional table `camera_client`.
- The same Camera–Client pair must be unique.
- Deleting either main record removes its pivot rows, not the other main record.
- Pivot timestamps record when an assignment is created or updated and are populated through `withTimestamps()` on both relationships.
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until confirmed against the MAX API contract.
- Redis, queues, and integration abstractions remain postponed.

## Known baseline issues

These issues predate the current relationship work:

- project-wide Pint reports formatting issues in six `lang/ru/*.php` files;
- Larastan reports that `ProfileValidationRules` is unused because its use is inside a Blade/Livewire component;
- Larastan reports a missing return in `UserFactory::withTwoFactor()`.

## Last session handoff

Date: 2026-09-12.

Summary:

- Phase 5 and the initial Client model are committed.
- The Phase 6 Camera ↔ Client relationship is complete locally and ready to commit.
- The pivot schema, bidirectional Eloquent relationships, duplicate protection, and cascading cleanup are covered by passing feature tests.
- Relationship methods have Larastan generic annotations and populate pivot timestamps.
- No application files were modified by Codex during the checkpoint update.

Next action on another machine:

- Pull the completed Phase 6 commit after it is pushed, start Sail, run migrations, and begin Phase 7 from “Next exact steps” above.
