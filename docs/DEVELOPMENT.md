# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 6 — Clients and relationships, in progress.

Phase 5 (Camera Eloquent model) is complete and committed. The Client model is committed. The Camera ↔ Client many-to-many relationship is currently uncommitted and not yet complete.

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
- the local `camera_client` pivot migration has been applied as batch 4;
- local relationship work is present but its relationship test currently fails.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, or event journal exists yet.

## Current learning task

Finish and verify the Camera ↔ Client many-to-many relationship without introducing later roadmap features.

The immediate blocker is in `test_client_attaches_to_the_camera_and_reads_it_from_both_sides`: the first attach creates the pivot row, then the second attach tries to insert the same `(camera_id, client_id)` pair and correctly triggers PostgreSQL unique violation `23505`.

## Completed work

- Created and tested the minimal Camera model.
- Created the `clients` table with unique `max_chat_id`.
- Added the Client model, integer cast, factory, and persistence test.
- Added a passing test that expects `QueryException` for duplicate `max_chat_id`.
- Created `camera_client` with foreign keys, cascading pivot cleanup, timestamps, and a composite unique constraint.
- Added preliminary `BelongsToMany` methods to Camera and Client.
- Learned that one `attach()` writes the relationship once; the inverse relation reads the same pivot row and must not attach it again.

## Current uncommitted changes

- `app/Models/Camera.php` — preliminary `clients()` relationship;
- `app/Models/Client.php` — preliminary `cameras()` relationship;
- `database/migrations/2026_09_12_120110_create_camera_client_table.php` — new pivot migration;
- `tests/Feature/Models/ClientTest.php` — Client constraint tests and unfinished relationship test;
- `database/migrations/2026_09_09_203321_create_cameras_table.php` — unrelated removal of explicit `255`, which should be restored to the committed version;
- documentation checkpoint updates.

## Verification at checkpoint

Run on 2026-09-12:

- `artisan migrate:status`: pivot migration is `Ran` in the current local database;
- `artisan test tests/Feature/Models`: 3 tests pass and the relationship test fails;
- failure: duplicate pivot pair caused by attaching the same relationship from both sides;
- targeted Pint: passes;
- Larastan: two existing baseline errors plus missing generic relationship types in Camera and Client;
- `git diff --check`: passes.

## Next exact steps

1. In the relationship test, attach the pair only once.
2. Assert that `camera_client` contains the expected `camera_id` and `client_id`.
3. Reload both models and verify that Camera can read the Client and Client can read the Camera.
4. Add Larastan generic PHPDoc to both `BelongsToMany` methods.
5. Add tests for duplicate pivot pairs and cascading pivot deletion while preserving the other main model.
6. Restore the unrelated Camera migration change (`string('name', 255)`).
7. Run the focused tests, Pint, Larastan, and `git diff --check` before a normal feature commit.
8. Update this file from “in progress” to “complete” after review.

## Decisions made

- Camera ↔ Client is many-to-many through conventional table `camera_client`.
- The same Camera–Client pair must be unique.
- Deleting either main record removes its pivot rows, not the other main record.
- Pivot timestamps currently exist; whether they are useful and should be populated with `withTimestamps()` remains an open decision.
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
- The pivot schema is sound and applied locally.
- Relationship methods are close but still need generic annotations.
- The relationship test demonstrates that the first attach works and that a second inverse attach is a duplicate, not a second relationship.
- No application files were modified by Codex during the checkpoint update.

Next action on another machine:

- Pull the branch containing this checkpoint, start Sail, run migrations, and continue from “Next exact steps” above.
