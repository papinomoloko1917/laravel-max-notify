# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

## Current phase

Phase 7 — Authentication and admin shell is complete.

Phase 8 — Camera management UI is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema.

Phase 9 — Client management UI is in progress. The list, creation flow, editing, and edit-validation coverage are committed and pushed through `57a1198`. Confirmed Client deletion is implemented locally; Camera assignment editing remains.

## Current repository state

Checkpoint date: 2026-09-26.

- branch: `main`;
- local `main` and `origin/main` point to `57a1198`;
- modified application files implement Client deletion and its tests;
- documentation files are modified to record this checkpoint;
- no unexpected untracked files remain;
- the current changes must be committed and pushed before continuing on another machine.

## Implemented application state

- Laravel 13.31 with the official Livewire Starter Kit;
- Livewire 4, Flux UI 2, Tailwind CSS 4, Vite 8, Pest, Pint, and Larastan;
- PostgreSQL 18 through Sail, with optional local Adminer;
- authenticated administration shell and profile/settings pages;
- Camera and Client models with a tested many-to-many relationship;
- complete Camera management for the current schema: list, creation, active-state filtering, pagination, editing, and confirmed deletion;
- protected Clients page with name ordering, pagination, MAX chat IDs, assigned-Camera counts through `withCount('cameras')`, and an empty state;
- Client creation with required name, integer/unique MAX chat ID validation, persistence, and field reset;
- separate Client edit state: `editingClientId`, `editName`, and `editMaxChatId`;
- `startEditing()` loads the selected Client with `findOrFail()` and fills edit state;
- each Client row has an edit action opening one shared Flux modal;
- the edit modal displays the selected name and MAX chat ID;
- a focused component test verifies that selection loads all three edit-state values.
- `updateClient()` validates and updates the selected Client, ignores that Client in the unique MAX chat ID rule, clears edit state, and closes the modal;
- the edit modal submits to `updateClient()` and provides Save and Close actions;
- a focused component test verifies persistence, state cleanup, and modal closing after a successful update.
- focused edit-validation tests verify that a missing name is rejected without changing the record or closing the modal, another Client's MAX chat ID is rejected while both records remain unchanged, and the edited Client may retain its own MAX chat ID.
- Client deletion uses separate selection state, a translated shared confirmation modal, explicit confirmation, state cleanup, and modal closing;
- focused tests separately verify deletion selection without persistence and confirmed deletion of only the selected Client.

Redis, Mailpit, Dahua/MAX HTTP clients, webhook handling, queues, duplicate protection, and event history remain intentionally postponed.

## Verification at checkpoint

- current `artisan test tests/Feature/ClientsTest.php`: **15 passing tests and 75 assertions**;
- current `artisan test tests/Feature/Models/ClientTest.php`: **4 passing tests and 9 assertions**;
- targeted Pint for the Client page, Client feature tests, and `lang/ru.json`: **passes**;
- `git diff --check`: **passes**;
- the full test suite and Larastan were not repeated for this checkpoint;
- previously known project-wide baseline issues remain: generated `lang/ru/*.php` formatting differences and two Larastan findings in Starter Kit-related code.

## Next exact learning block

Begin Camera assignment editing for Clients without persisting changes yet.

1. Add separate edit state for selected Camera IDs.
2. Load the edited Client's currently assigned Camera IDs in `startEditing()`.
3. Present available Cameras in the existing edit modal with clear status information.
4. Extend the selection test to verify that existing assignments are loaded; persistence with `sync()` belongs to the following block.

## Decisions to preserve

- Camera ↔ Client is many-to-many through `camera_client`; duplicate pairs are forbidden and deleting either model removes only its pivot rows.
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until the MAX API contract is confirmed.
- Client list Camera totals are calculated by PostgreSQL through `withCount('cameras')`.
- successful Client creation resets its fields but intentionally leaves the creation modal open.
- edit and create fields use separate Livewire state.
- Client update validation must not reject the unchanged MAX chat ID belonging to the selected Client.
- failed Client update validation must preserve the record and keep the edit modal open for correction.
- the Dahua webhook will use normal Laravel HTTP routing, not Livewire.
- external API calls do not belong in Livewire components or Eloquent models.
- Redis and queues are introduced only when a concrete requirement appears.
- application code remains learner-written unless explicit permission is given; Codex maintains checkpoint documentation.

## Moving to another machine

On this machine, after reviewing the documentation diff:

```bash
git add lang/ru.json resources/views/pages/clients/⚡index.blade.php tests/Feature/ClientsTest.php docs/DEVELOPMENT.md docs/ROADMAP.md docs/ARCHITECTURE.md
git commit -m "feat: add client deletion"
git push
```

On the other machine:

```bash
git pull
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Feature/ClientsTest.php
```
