# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

## Current phase

Phase 7 — Authentication and admin shell is complete.

Phase 8 — Camera management UI is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema.

Phase 9 — Client management UI is in progress. The list, creation flow, edit-state preparation, and successful updating are committed and pushed through `1444211`. Invalid-update coverage has started as a local WIP; Client deletion is not implemented.

## Current repository state

Checkpoint date: 2026-09-25.

- branch: `main`;
- local `main` and `origin/main` point to `1444211` before this WIP checkpoint is committed;
- modified application files:
  - `tests/Feature/ClientsTest.php`;
- modified documentation files:
  - `docs/DEVELOPMENT.md`;
  - `docs/ROADMAP.md`;
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

Redis, Mailpit, Dahua/MAX HTTP clients, webhook handling, queues, duplicate protection, and event history remain intentionally postponed.

## Verification at checkpoint

- committed baseline at `1444211`: `artisan test tests/Feature/ClientsTest.php` has **10 passing tests and 38 assertions**;
- current WIP run: **11 tests total, 10 pass and 1 fails after 41 assertions**;
- the failing WIP test is `test_clear_name_client()`;
- failure reason: Livewire testing has no `assertNoDispatched()` method; the intended assertion is `assertNotDispatched()`;
- targeted Pint for the current WIP `ClientsTest.php`: **passes**;
- `git diff --check`: **passes**;
- the full test suite and Larastan were not repeated for this checkpoint;
- previously known project-wide baseline issues remain: generated `lang/ru/*.php` formatting differences and two Larastan findings in Starter Kit-related code.

## Next exact learning block

Complete validation coverage for Client editing; do not start deletion yet.

1. Finish `test_clear_name_client()`: use the specific `required` error assertion, verify all edit-state values remain available, cast the expected MAX chat ID to string, and replace `assertNoDispatched()` with `assertNotDispatched()`.
2. Prefer the clearer test name `test_client_name_is_required_when_updating()`.
3. Add focused coverage showing that another Client's MAX chat ID is rejected and neither Client is changed.
4. Add coverage proving that a Client can keep its own unchanged MAX chat ID while changing another field, then run the full Clients tests and targeted Pint.

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
git add docs/DEVELOPMENT.md docs/ROADMAP.md tests/Feature/ClientsTest.php
git commit -m "wip: checkpoint client edit validation"
git push
```

On the other machine:

```bash
git pull
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Feature/ClientsTest.php
```
