# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

## Current phase

Phase 7 — Authentication and admin shell is complete.

Phase 8 — Camera management UI is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema.

Phase 9 — Client management UI is in progress. The list and creation flow are committed and pushed through `e3471c9`. Client edit-state loading and its modal are implemented and verified locally but are not committed yet. Updating and deletion are not implemented.

## Current repository state

Checkpoint date: 2026-09-22.

- branch: `main`;
- local `main` and `origin/main` point to `e3471c9` before this checkpoint is committed;
- modified application files:
  - `resources/views/pages/clients/⚡index.blade.php`;
  - `tests/Feature/ClientsTest.php`;
- modified documentation files:
  - `docs/DEVELOPMENT.md`;
  - `docs/ROADMAP.md`;
  - `docs/ARCHITECTURE.md`;
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
- the edit modal displays the selected name and MAX chat ID and currently contains only a Close action;
- a focused component test verifies that selection loads all three edit-state values.

Redis, Mailpit, Dahua/MAX HTTP clients, webhook handling, queues, duplicate protection, and event history remain intentionally postponed.

## Verification at checkpoint

- `artisan test tests/Feature/ClientsTest.php`: **9 tests pass, 32 assertions**;
- targeted Pint for `resources/views/pages/clients/⚡index.blade.php`: **passes**;
- the focused edit-state test passes with 4 assertions;
- `git diff --check`: **passes**;
- the full test suite and Larastan were not repeated for this checkpoint;
- previously known project-wide baseline issues remain: generated `lang/ru/*.php` formatting differences and two Larastan findings in Starter Kit-related code.

## Next exact learning block

Continue Client editing; do not start deletion yet.

1. Write a focused Livewire component test for successfully updating the selected Client.
2. Implement `updateClient()` with validation for edit fields, including uniqueness of `max_chat_id` while ignoring the Client currently being edited.
3. Connect the edit modal to the action with a form and Save button.
4. Verify that the database changes, edit state is cleared, and the modal closes only after a successful update.

Invalid-update coverage should follow after the successful update path works and has been reviewed.

## Decisions to preserve

- Camera ↔ Client is many-to-many through `camera_client`; duplicate pairs are forbidden and deleting either model removes only its pivot rows.
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until the MAX API contract is confirmed.
- Client list Camera totals are calculated by PostgreSQL through `withCount('cameras')`.
- successful Client creation resets its fields but intentionally leaves the creation modal open.
- edit and create fields use separate Livewire state.
- Client update validation must not reject the unchanged MAX chat ID belonging to the selected Client.
- the Dahua webhook will use normal Laravel HTTP routing, not Livewire.
- external API calls do not belong in Livewire components or Eloquent models.
- Redis and queues are introduced only when a concrete requirement appears.
- application code remains learner-written unless explicit permission is given; Codex maintains checkpoint documentation.

## Moving to another machine

On this machine, after reviewing the documentation diff:

```bash
git add docs/DEVELOPMENT.md docs/ROADMAP.md docs/ARCHITECTURE.md \
  'resources/views/pages/clients/⚡index.blade.php' tests/Feature/ClientsTest.php
git commit -m "feat: prepare client editing"
git push
```

On the other machine:

```bash
git pull
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Feature/ClientsTest.php
```
