# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise and update it after a meaningful learning block.

## Current phase

Phase 7 — Authentication and admin shell is complete and committed.

Phase 8 — Camera management UI is in progress. Camera listing, creation, filtering, pagination, and editing are committed and pushed through `02ce63c`; safe Camera deletion preparation is implemented locally but not committed.

## Current state

- Laravel 13.31 with the official Livewire Starter Kit;
- PHP 8.4 locally and PHP 8.5 in Sail;
- Livewire 4, Flux UI 2, Tailwind CSS 4, and Vite 8;
- PostgreSQL 18 as the application and test database;
- Sail services: Laravel, PostgreSQL, and optional local Adminer;
- Pest, Pint, and Larastan are installed;
- authentication, profile/settings pages, and the protected dashboard come from the Starter Kit;
- Camera, Client, their many-to-many relationship, and relationship tests are committed in `48844e8`;
- local `main` and `origin/main` point to `02ce63c` before the current uncommitted deletion-preparation slice;
- a protected `/cameras` Livewire page and named route `cameras.index` are committed in `48f8ba2`;
- the sidebar contains a Cameras link with a custom Flux-compatible `cctv` icon;
- feature tests cover guest and authenticated access, ordered camera rendering, active/inactive statuses, and the empty state;
- the Cameras page loads cameras ordered by name, creates and edits validated cameras, filters by active state, distinguishes statuses, paginates, and shows an empty state;
- Camera and User seeders provide manual development data only in the `local` environment;
- the predictable development user is repeatable through `updateOrCreate()` and has a verified email.

Redis and Mailpit are not configured. No Dahua webhook, external API clients, queue jobs, duplicate protection, or event journal exists yet.

## Current learning task

Continue the separate Camera deletion block by adding confirmation UI without performing deletion yet.

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
- Added database-backed Camera pagination with `WithPagination`, a computed paginator, and Flux UI controls.
- Added focused Livewire tests for successful creation, required-name validation, active-status filtering, page navigation, and filter-driven page reset.
- Added separate Camera edit state, a row action, a Flux edit modal, validated updating, and automatic modal closing after a successful update.
- Added focused tests for loading the selected Camera into edit state and successfully updating its name and active status.
- Added a focused invalid-edit test proving that a required-name failure preserves the Camera and does not close the modal.
- Started the deletion block with separate selected-Camera state and a tested `startDeleting()` action that does not delete anything.

## Recent Camera UI work

- commit `6c4e712` centralizes filter options, renders them through a loop, shows the selected label in the trigger, translates the Add button, and replaces “By name” with “All”;
- commit `80d73f0` synchronizes the Camera UI checkpoint documentation;
- commit `08d9e2e` adds database-backed pagination, the Flux paginator, and focused creation/filter/pagination tests; it is present on local and remote `main`.
- commit `20ac704` adds Camera editing UI and behavior, the `Edit the camera` translation, two focused component tests, and the prior checkpoint documentation; it is present on local and remote `main`.
- commit `6daf730` adds invalid-edit coverage and refreshes the checkpoint documentation; it is present on local and remote `main`.
- commit `02ce63c` completes the Camera editing checkpoint documentation; it is present on local and remote `main`.
- the current uncommitted slice adds deletion-selection state, `startDeleting()`, and a test proving that selection loads the correct Camera without deleting it.

## Verification at checkpoint

Checkpoint date: 2026-09-18.

- `artisan test tests/Feature/CamerasTest.php`: 12 tests pass with 42 assertions;
- `artisan test tests/Feature/Models`: 7 tests pass with 15 assertions;
- targeted Pint for the Cameras page, migrations, providers, bootstrap file, and affected factory passes;
- the full suite runs 44 tests: 43 pass and one is skipped; the known empty Starter Kit test is still marked risky;
- `git diff --check` passes;
- Sail is running and all migrations are applied;
- `UserSeeder` succeeds on two consecutive local runs and produces a verified `test@mail.ru` user;
- Larastan still has only the two known baseline issues listed below.

Current Camera creation/filter slice:

- the existing four Cameras feature tests still pass with eight assertions;
- the creation action now validates both fields, persists the Camera, clears the name, and reloads the ordered list;
- the filter defaults to `all`, filters in PostgreSQL for `active` and `inactive`, and reloads through `updatedFilter()`;
- Pint now deliberately accepts anonymous-class opening braces on the same line, matching Blade Formatter 1.44.4;
- Pint now also accepts Blade Formatter's `fn()` spacing for short arrow functions;
- the Cameras page and all mechanically aligned PHP files pass targeted Pint;
- creation and filter behavior now have focused Livewire component tests;
- the `all` option now displays the translated “All” label;
- full-project Pint still reports only the six known generated Russian language-file issues.

Current pagination state:

- the computed `LengthAwarePaginator`, `WithPagination`, and filter page reset are in place;
- the standalone Flux paginator now receives the computed paginator object through a valid bound prop;
- pagination remains covered as part of the 12 passing Cameras tests;
- the filter resets pagination to page one through `resetPage()`;
- the computed method is public and has an explicit `LengthAwarePaginator` return type;
- the UI uses Flux 2.19's standalone pagination component, while Laravel/Livewire own the paginator state and query;
- targeted Pint passes.

## Next exact steps

1. Commit and push the current deletion-selection slice and checkpoint documentation before switching machines.
2. Add a per-row Delete trigger and one shared confirmation modal showing `deletingCameraName`; include Cancel only.
3. Do not implement actual deletion or an active confirmation button until that UI step is reviewed.

## Decisions made

- Camera ↔ Client is many-to-many through conventional table `camera_client`.
- The same Camera–Client pair must be unique.
- Deleting either main record removes its pivot rows, not the other main record.
- Pivot timestamps record when an assignment is created or updated and are populated through `withTimestamps()` on both relationships.
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until confirmed against the MAX API contract.
- The Cameras page is an authenticated Livewire administration page; webhook traffic will remain outside Livewire.
- Camera management is being introduced incrementally: listing, creation, filtering, pagination, and editing exist; deletion is now an isolated destructive-action block and has not yet been implemented.
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

Date: 2026-09-18.

Summary:

- local `main` and `origin/main` point to `02ce63c` before the current uncommitted slice;
- Camera editing and invalid-edit coverage are committed and pushed; the editing block is complete.
- Camera deletion preparation now stores the selected Camera ID/name and has a focused test proving the Camera remains in the database;
- no delete method, Delete trigger, confirmation modal, or destructive UI action exists yet;
- Cameras tests pass: 12 tests, 42 assertions; targeted Pint and `git diff --check` pass;
- the full suite runs 44 tests with 43 passed and one skipped; the pre-existing empty Starter Kit test remains risky;
- deletion-selection code, its test, and this checkpoint documentation are modified but not committed.

Next action on another machine:

- pull the new checkpoint commit, then add the Delete trigger and non-destructive confirmation modal; do not implement deletion yet.
