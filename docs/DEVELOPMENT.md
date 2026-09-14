# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 7 — Authentication and admin shell is complete and committed.

Phase 8 — Camera management UI is in progress. The first read-only list and safe development-seeder slice is complete, committed, and pushed in `35039fb`.

## Current state

- Laravel 13.31 with the official Livewire Starter Kit;
- PHP 8.4 locally and PHP 8.5 in Sail;
- Livewire 4, Flux UI 2, Tailwind CSS 4, and Vite 8;
- PostgreSQL 18 as the application and test database;
- Sail services: Laravel, PostgreSQL, and optional local Adminer;
- Pest, Pint, and Larastan are installed;
- authentication, profile/settings pages, and the protected dashboard come from the Starter Kit;
- Camera, Client, their many-to-many relationship, and relationship tests are committed in `48844e8`;
- local `HEAD` and `origin/main` both point to `35039fb`, with the Phase 8 creation/filter work still uncommitted in the working tree;
- a protected `/cameras` Livewire page and named route `cameras.index` are committed in `48f8ba2`;
- the sidebar contains a Cameras link with a custom Flux-compatible `cctv` icon;
- feature tests cover guest and authenticated access, ordered camera rendering, active/inactive statuses, and the empty state;
- the Cameras page loads cameras ordered by name, renders a Flux table, distinguishes active/inactive cameras, and shows an empty state;
- Camera and User seeders provide manual development data only in the `local` environment;
- the predictable development user is repeatable through `updateOrCreate()` and has a verified email.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, or event journal exists yet.

## Current learning task

Finish the first Camera creation slice and the closely related active-status list filter with focused Livewire component tests.

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

## Current work since `35039fb`

- `resources/views/pages/cameras/⚡index.blade.php` — Camera creation form/action and initial active/inactive filter attempt; the filter query exists but does not yet reload when the bound value changes;
- `lang/ru.json` — translations added for the creation modal and status filter;
- `pint.json`, existing anonymous class declarations, and existing arrow functions — formatting policy aligned with Blade Formatter; behavior is unchanged;
- checkpoint documentation synchronized by Codex.

## Verification at checkpoint

Checkpoint date: 2026-09-14.

- `artisan test tests/Feature/CamerasTest.php`: 4 tests pass with 8 assertions;
- `artisan test tests/Feature/Models`: 7 tests pass with 15 assertions;
- targeted Pint for the Cameras page, migrations, providers, bootstrap file, and affected factory passes;
- the full suite runs 36 tests: 35 pass, one is skipped, and one is marked risky;
- `git diff --check` passes;
- Sail is running and all migrations are applied;
- `UserSeeder` succeeds on two consecutive local runs and produces a verified `test@mail.ru` user;
- Larastan still has only the two known baseline issues listed below.

Current Camera creation attempt:

- the existing four Cameras feature tests still pass with eight assertions;
- the creation action now validates both fields, persists the Camera, clears the name, and reloads the ordered list;
- Pint now deliberately accepts anonymous-class opening braces on the same line, matching Blade Formatter 1.44.4;
- Pint now also accepts Blade Formatter's `fn()` spacing for short arrow functions;
- the Cameras page and all mechanically aligned PHP files pass targeted Pint;
- creation-specific tests have not been added yet;
- filter-specific tests have not been added yet, and changing `$filter` currently does not invoke `loadCameras()`;
- full-project Pint still reports only the six known generated Russian language-file issues.

## Next exact steps

1. Add focused Livewire tests for successful Camera creation, validation failure, state reset, and immediate list refresh.
2. Make the status filter reload the collection when its Livewire property changes and use `all` rather than `name` for the unfiltered state.
3. Add focused tests for active, inactive, and unfiltered results.
4. Verify the combined Camera creation/filter slice and then prepare logical commits.

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
- Pint permits same-line opening braces for anonymous classes so that its output agrees with the configured Blade Formatter used on save.
- Pint permits no space after `fn` for short arrow functions for the same formatter compatibility reason.
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
- Commit `35039fb` contains the list tests, local-only seeder safety, documentation, and the rule that Codex maintains project docs.
- User and Camera seeders are local-only; the predictable development user is repeatable and verified.
- A first Camera creation form and action now exist as uncommitted learner work.
- The creation action is structurally complete, but focused creation tests are still missing.
- A database-backed active/inactive filter has been started; it needs a Livewire property-update hook before it reacts in the browser.
- All 36 project tests run: 35 pass, one is skipped, and one is marked risky; the Cameras file contributes four passing tests and eight assertions.
- The two existing Larastan baseline findings remain unchanged.
- Blade Formatter and Pint are now aligned for anonymous classes and short arrow functions; affected files pass targeted formatting checks.
- At the time of this checkpoint, the entire Phase 8 creation/filter and formatter-alignment worktree is uncommitted; it must be committed and pushed before switching machines.

Next action on another machine:

- First commit and push the current WIP so it is available on the other machine. After pulling it there, complete the focused Camera creation and status-filter tests described in “Next exact steps”.
