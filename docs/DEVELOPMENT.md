# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

## Current phase

Phase 7 — Authentication and admin shell is complete.

Phase 8 — Camera management UI is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema.

Phase 9 — Client management UI is complete, committed, and pushed through `da1aca1`, including validated Camera assignment persistence.

Phase 10 — Dahua IVS webhook exploration is complete using the real DHI-NVR4232-4KS2/L and the now-removed diagnostic route.

Phase 11 — The minimal synchronous webhook boundary is complete and verified through Postman and the real NVR.

## Current repository state

Checkpoint date: 2026-09-26.

- branch: `main`;
- local `main` and `origin/main` point to `da1aca1`;
- the production `/webhooks/dahua` route, controller, feature tests, and synchronized documentation are staged for commit;
- the temporary `/dahua-probe` route and its route-file imports have been removed;
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
- the Client edit modal shows an independently paginated Camera table with checkboxes backed by `editCameraIds`;
- `startEditing()` loads current Camera IDs, while `updateClient()` validates every submitted ID and uses `sync()` to make pivot rows match the selected set;
- focused tests verify replacement of Camera assignments and rejection of a nonexistent Camera ID without changing Client or pivot data.
- a reversible Camera migration adds nullable unique `webhook_username` and nullable `webhook_password` fields so existing Cameras need not be configured immediately;
- the Camera model accepts webhook credentials, hashes `webhook_password` automatically, and hides the stored hash from array/JSON serialization;
- focused model tests verify hashing, password-hash matching, serialization hiding, and database-enforced username uniqueness.
- `/webhooks/dahua` is a normal GET route handled by an invokable controller outside browser-user authentication;
- the controller identifies an active Camera by Basic username, verifies the supplied password against its stored hash, and returns plain-text `200 OK` only on success;
- missing credentials, unknown usernames, wrong passwords, and inactive Cameras all receive the same plain-text `401 Unauthorized` response;
- five focused webhook feature tests cover the success path and all four authentication rejection paths.
- after successful authentication, query metadata requires exact `event=ivs`, an integer channel of at least one, and a non-empty rule label of at most 100 characters;
- malformed authenticated metadata receives explicit plain-text `400 Invalid webhook request` rather than a browser-oriented redirect;
- three additional focused tests cover a non-IVS event, a non-integer channel, and a missing rule.
- accepted requests are logged with Camera ID/name and validated event/channel/rule only; credentials and authorization headers are excluded;
- the production `/webhooks/dahua` endpoint was successfully exercised through both Postman and the real NVR on 2026-09-27, producing matching accepted-event logs for Camera ID 7 with `event=ivs`, `channel=7`, and `rule=perimeter`.

Redis, Mailpit, Dahua/MAX HTTP clients, webhook event processing, queues, duplicate protection, and event history remain intentionally postponed.

## Confirmed Dahua probe behavior

Observed on 2026-09-26 with DHI-NVR4232-4KS2/L firmware `V4.003.0000000.1.R` and DH-IPC-HFW2249SP-S-IL-0280B. IVS, rather than SMD, is the intended production event source:

- the NVR can reach the Sail application through the Windows host LAN address;
- both the initial SMD experiment and the intended IVS "Send command" action make an HTTP `GET` request to the configured URL;
- the observed request had no body and no `Content-Type` header;
- the observed NVR headers were limited to `Accept`, `Host`, and `Connection`;
- query parameters such as `probe`, `event`, and `channel` are static values configured in the command URL, not device-generated event payload; the initial `event=smd` value therefore remained visible even when IVS caused the request and did not identify the actual analytic source;
- the application sees the Docker gateway address `172.18.0.1`, so source IP is not a reliable Camera identity in this environment;
- two IVS requests eight seconds apart were correlated with two separate physical triggers while NVR anti-dither was set to five seconds;
- no duplicate delivery for a single controlled IVS trigger has been observed so far;
- the static request contains no device-generated event identifier or state that would distinguish a retry from a separate detection if identical calls occur later.
- with IVS Authentication enabled and temporary test credentials configured, the NVR sent an `Authorization` header on its first request without receiving a prior `401` challenge;
- PHP also exposed the `php-auth-user` and `php-auth-pw` header names, confirming that the request used HTTP Basic authentication; their values were not logged;
- the confirmed representative IVS metadata was `event=ivs`, `channel=13`, and `rule=perimeter`, all configured statically in the command URL.

The temporary probe logged only request metadata and header names, not header values or body contents. It was removed after the production endpoint was verified.

## Verification at checkpoint

- current `artisan test tests/Feature/ClientsTest.php`: **16 passing tests and 88 assertions**;
- current `artisan test tests/Feature/Models`: **7 passing tests and 15 assertions**;
- current focused `artisan test tests/Feature/Models/CameraTest.php` after adding webhook credentials: **5 passing tests and 10 assertions**;
- current focused `artisan test tests/Feature/DahuaWebhookTest.php`: **8 passing tests and 18 assertions**;
- full `artisan test`: **71 tests, 226 assertions, 1 skipped and 1 risky**; the risky Starter Kit security test performs no assertions and is unrelated to the webhook changes;
- targeted Pint for the Client page, Client feature tests, and `lang/ru.json`: **passes**;
- `git diff --check`: **passes**;
- Larastan was not repeated for this checkpoint;
- previously known project-wide baseline issues remain: generated `lang/ru/*.php` formatting differences and two Larastan findings in Starter Kit-related code.

## Next exact learning block

Commit and push the completed Phase 11 checkpoint. Then begin Phase 12 by deciding the minimal event/rule and allowed-time-window configuration required for the tested Camera; do not introduce queues, Redis, event persistence, or external HTTP clients yet.

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

On this machine, after reviewing the staged Phase 11 diff:

```bash
git add README.md docs/DEVELOPMENT.md docs/ROADMAP.md docs/ARCHITECTURE.md
git commit -m "feat: add authenticated Dahua webhook"
git push
```

On the other machine:

```bash
git pull
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Feature/DahuaWebhookTest.php
```
