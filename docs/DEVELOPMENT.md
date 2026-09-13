# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 7 — Authentication and admin shell is implemented locally.

Phase 8 — Camera management UI has started with a read-only camera list. The list implementation still needs its focused feature test and verification before this block is considered complete.

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
- a protected `/cameras` Livewire page and named route `cameras.index` exist locally;
- the sidebar contains a Cameras link with a custom Flux-compatible `cctv` icon;
- feature tests cover guest and authenticated access to the Cameras page;
- the Cameras page locally loads cameras ordered by name, renders a Flux table, distinguishes active/inactive cameras, and shows an empty state;
- Camera and User seeders are present locally for manual development data.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, or event journal exists yet.

## Current learning task

Finish the first Phase 8 block: add focused test coverage for the read-only Cameras list, run the targeted checks, review the complete local change set, and commit/push it before moving to camera creation or editing.

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
- Began a read-only Camera list with ordering, active/inactive badges, and an empty state.

## Current uncommitted changes

- Cameras admin shell: route, Livewire page, sidebar entry, translations, custom `cctv` icon, and access test;
- initial read-only Cameras list and empty state;
- Camera factory and Camera/User seeders for local sample data;
- intentional formatting/customization changes in Starter Kit Blade views and the published Flux navlist group;
- these checkpoint documentation updates.

## Verification at checkpoint

Checkpoint date: 2026-09-14.

- Before the read-only list was added, `artisan test tests/Feature/CamerasTest.php` passed with 2 tests and 4 assertions;
- `git diff --check` passes for the current working tree;
- PHP syntax checks pass for `CameraSeeder` and `CameraFactory`;
- current Sail tests and Pint were not run because Docker/Podman was stopped;
- the read-only camera list still lacks the focused feature test described below;
- previous Phase 6 verification remains recorded in Git history/documentation and was completed before commit `48844e8`.

## Next exact steps

1. On this machine, commit and push all intended checkpoint changes so they are available on the next machine.
2. On the next machine, pull `main`, start Sail, and run migrations/seeders as needed.
3. Add `: void` to the Cameras page `mount()` method.
4. Add a feature test that creates active and inactive cameras in reverse alphabetical order and verifies their names, statuses, and rendered order.
5. Run the focused Cameras test and targeted Pint; review before adding create/edit/delete behavior.

## Decisions made

- Camera ↔ Client is many-to-many through conventional table `camera_client`.
- The same Camera–Client pair must be unique.
- Deleting either main record removes its pivot rows, not the other main record.
- Pivot timestamps record when an assignment is created or updated and are populated through `withTimestamps()` on both relationships.
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until confirmed against the MAX API contract.
- The Cameras page is an authenticated Livewire administration page; webhook traffic will remain outside Livewire.
- The first Camera UI slice is deliberately read-only; CRUD will be introduced incrementally.
- Starter Kit Repository and Documentation links were removed because they were template examples, not application navigation.
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
- Phase 7 is implemented locally: protected Cameras route/page, navigation, custom icon, and access tests.
- Phase 8 has started with a read-only ordered Cameras table, status badges, an empty state, factory variation, and development seeders.
- The list-specific feature test has not yet been written.
- Docker was stopped at the checkpoint, so the latest application changes have not received a fresh Sail/Pint run.
- Codex modified only documentation during this checkpoint.

Next action on another machine:

- After the current changes are committed and pushed, pull `main`, start Sail, finish the focused Cameras list test, and run the checks in “Next exact steps”.
