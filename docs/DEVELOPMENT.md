# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

## Current phase

Phase 7 — Authentication and admin shell is complete.

Phase 8 — Camera management UI is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema.

Phase 9 — Client management UI is complete, committed, and pushed through `da1aca1`, including validated Camera assignment persistence.

Phase 10 — Dahua IVS webhook exploration is complete using the real DHI-NVR4232-4KS2/L and the now-removed diagnostic route.

Phase 11 — The minimal synchronous webhook boundary is complete and verified through Postman and the real NVR.

Phase 12 — Per-Camera notification-window storage, timezone configuration, the isolated business rule, webhook integration, and validated Camera UI editing are complete, committed, and pushed through `04fdb86`.

Phase 13 — Dahua snapshot HTTP exploration is complete. Successful browser/Postman image retrieval and representative authentication and invalid-channel failures are confirmed against the real NVR, and the credential disclosed during exploration has been rotated.

Phase 14 — The Dahua snapshot client is complete for the currently understood contract. Automated tests cover success and representative failures, explicit request limits are configured, and a real-device smoke check confirmed Laravel/Guzzle Digest negotiation and JPEG retrieval.

## Current repository state

Checkpoint date: 2026-10-10.

- branch: `main`;
- the working tree was clean before this documentation-only handoff update;
- `main` matches `origin/main` at `9ffbe93` (`feat: add Dahua request timeouts`);
- the Phase 11 baseline is commit `c90ac2f` (`feat: add authenticated Dahua webhook`);
- the notification-window storage and isolated rule checkpoint is commit `ea19c4a` (`feat: add camera notification windows`);
- the webhook time-window integration checkpoint is commit `f8843d2` (`feat: apply camera notification windows`) and is pushed to `origin/main`;
- Camera notification-window edit state, time inputs, translations, loading coverage, and synchronized documentation are committed and pushed in `3bd2175`;
- commit `37dee28` is the prior WIP checkpoint from the other machine;
- commit `04fdb86` completes nullable `H:i` validation, persistence, explicit empty-string-to-null conversion, focused save/clear/invalid-window tests, formatting tooling, and synchronized Phase 12 documentation;
- reciprocal `required_with` validation rejects partial windows, while `different:editNotifyUntil` rejects equal boundaries; focused tests assert the exact rules, unchanged database values, and an open modal;
- Prettier Blade/Tailwind packages and the project-wide formatting baseline are committed in `04fdb86`;
- commit `a8a99bc` contains the intentional `pint.json` simplification, the reviewed mechanical concatenation formatting, and synchronized documentation; `main` is pushed and matches `origin/main`;
- commit `75446e9` adds the environment-backed Digest snapshot client and its successful/error response tests;
- commit `9ffbe93` adds explicit connection/overall time limits, connection-failure coverage, the successful real-NVR smoke-test checkpoint, and synchronized Phase 14 documentation;
- the current Pint configuration removes the special `Pint/laravel_blade` and brace-position overrides, retaining the Laravel preset with one-space concatenation;
- the one-space concatenation rule has been applied mechanically to the ten affected PHP files, the temporary backup is removed, and full Pint/tests pass;
- the temporary `/dahua-probe` route remains removed;
- documentation is synchronized through the current Pint configuration decision.

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
- a reversible migration adds nullable PostgreSQL `time` columns `notify_from` and `notify_until` to each Camera;
- Camera mass assignment accepts both notification-window fields without treating them as datetimes;
- a focused PostgreSQL model test verifies that submitted `HH:MM` values persist and reload as `HH:MM:SS`, including an overnight `21:00–06:00` window.
- application timezone is environment-driven through `APP_TIMEZONE`; the local/example installation uses `Europe/Moscow`, and Sail verification reports the expected `+03:00` offset.
- the isolated `NotificationWindow` service receives the current time explicitly and does not read the global clock itself;
- its unit tests cover daytime and overnight windows, inclusive starts, exclusive ends, unrestricted `null/null`, rejected partial configuration, and rejected equal boundaries;
- `DahuaWebhookController` applies the identified Camera's window after authentication and metadata validation; outside-window events receive plain-text `200 Ignored` before accepted-event logging, while allowed events continue with `200 OK`;
- focused feature tests freeze the Laravel clock and verify both an allowed `22:00` and rejected `12:00` request for an overnight `21:00–06:00` window;
- Camera edit state now loads stored notification times as `HH:MM`, maps unrestricted `null/null` to empty strings, and displays two Flux time inputs;
- focused Livewire tests cover loading an overnight `21:00–06:00` window and loading unrestricted empty values;
- Camera updates validate each non-empty time as `H:i`, persist both values, and explicitly map two empty inputs back to unrestricted `null/null`;
- focused Livewire tests cover saving `21:00–06:00` and clearing an existing window;
- Camera form validation accepts only unrestricted `null/null` or two distinct `H:i` boundaries; partial and equal-boundary windows are rejected before persistence.

Redis, Mailpit, the MAX HTTP client, webhook event processing, queues, duplicate protection, and event history remain intentionally postponed. The Dahua snapshot client is implemented but deliberately not yet connected to the webhook.

## Confirmed Dahua snapshot behavior

Observed on 2026-10-10 against the real NVR:

- a `GET` request to `/cgi-bin/snapshot.cgi?channel=<channel>` returns the current Camera image for a valid recorder channel;
- the endpoint uses HTTP Digest authentication with a dedicated NVR account;
- both a browser and Postman successfully displayed the response as an image;
- the successful response was `200 OK`, `image/jpeg`, with a nonzero `Content-Length` (the exact image size is expected to vary);
- a wrong password returns `401 Unauthorized`, `text/plain;charset=utf-8`, and the text error `Invalid Authority!`;
- a nonexistent channel returns `400 Bad Request`, `text/plain;charset=utf-8`, and the text error `Bad Request!`;
- real recorder addresses and credentials are intentionally excluded from the repository;
- the credential disclosed during exploration was rotated on the NVR and is no longer valid.

The representative success and failure contract is implemented in the minimal client. Postman established the device contract, automated HTTP fakes protect application behavior, and the Tinker smoke test proved real Digest negotiation through Laravel/Guzzle.

## Current Dahua client slice

- environment-backed `services.dahua` configuration supplies the single current NVR base URL, username, and password without committing real credentials;
- `App\Services\DahuaClient::snapshot(int $channel)` performs a Digest-configured GET to `/cgi-bin/snapshot.cgi` and passes the channel as a query parameter;
- the successful response body is returned as raw string bytes;
- a focused HTTP-fake test prevents stray network requests, verifies the returned JPEG bytes, and verifies the exact GET URL for channel 13;
- `4xx/5xx` responses are converted to Laravel `RequestException` through `Response::throw()` before content validation;
- a successful response whose `Content-Type` is not exactly `image/jpeg` is rejected with `UnexpectedValueException` rather than returned as snapshot bytes;
- environment-backed defaults limit connection establishment to 3 seconds and the overall request to 10 seconds;
- a focused failed-connection fake verifies that Laravel `ConnectionException` propagates from the client;
- focused verification: **4 passing tests and 5 assertions**, with Pint passing for the client, test, and service configuration;
- full verification after this slice: **93 tests, 266 assertions, 1 skipped and the same 1 known risky Starter Kit test**; full Pint passes;
- a real-device Tinker smoke check on 2026-10-10 successfully retrieved channel 13 through this client; the returned binary began with the JPEG `FF D8` signature, proving real Laravel/Guzzle Digest negotiation;
- webhook integration remains intentionally unimplemented; the client was verified independently first.

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
- current `artisan test tests/Feature/Models`: **10 passing tests and 22 assertions**;
- current focused `artisan test tests/Feature/Models/CameraTest.php` after adding webhook credentials: **5 passing tests and 10 assertions**;
- current focused `artisan test tests/Feature/Models/CameraTest.php` after adding notification-window storage: **6 passing tests and 13 assertions**;
- current focused `artisan test tests/Feature/DahuaWebhookTest.php`: **10 passing tests and 22 assertions**;
- current focused `artisan test tests/Unit/NotificationWindowTest.php`: **10 passing tests and 10 assertions**;
- current `artisan test tests/Feature/CamerasTest.php`: **18 passing tests and 66 assertions**;
- current focused Camera/NotificationWindow/Dahua regression run after changing Pint: **38 passing tests and 98 assertions**;
- current focused `artisan test tests/Feature/Services/DahuaClientTest.php`: **4 passing tests and 5 assertions**;
- full `artisan test`: **93 tests, 266 assertions, 1 skipped and 1 risky**; only the previously known Starter Kit security test is risky;
- full Pint with the intentional one-space concatenation configuration: **passes**;
- frontend production build: **passes**; Vite reports only the optional `fontaine` optimized-fallback notice;
- `git diff --check`: **passes**;
- Larastan reports only the **2 previously known Starter Kit-related findings**; adding the controller return type resolved the webhook finding, and no finding points to the Phase 12 code;
- previously known generated `lang/ru/*.php` formatting differences remain outside this block.

## Next exact learning block

Begin Phase 15 by exploring the real MAX Messenger upload/send HTTP workflow manually before implementing a MAX client. First determine the official API documentation, authentication header, upload creation/upload/send sequence, successful responses, and representative failures. Do not place the real token in Git, exported Postman collections, screenshots, or chat.

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

Commit and push this documentation-only handoff update. On the other machine:

```bash
git pull
./vendor/bin/sail up -d
./vendor/bin/sail npm ci
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Feature/CamerasTest.php
./vendor/bin/sail artisan test tests/Unit/NotificationWindowTest.php
./vendor/bin/sail artisan test tests/Feature/Models/CameraTest.php
./vendor/bin/sail artisan test tests/Feature/DahuaWebhookTest.php
./vendor/bin/sail artisan test tests/Feature/Services/DahuaClientTest.php
```

Because `.env` is intentionally not committed, configure these values locally before clearing configuration:

- `APP_TIMEZONE=Europe/Moscow`;
- `DAHUA_BASE_URL`;
- `DAHUA_USERNAME`;
- `DAHUA_PASSWORD`;
- optionally override `DAHUA_CONNECT_TIMEOUT=3` and `DAHUA_TIMEOUT=10`.

Transfer real credentials through a secure channel, never Git or chat. Automated tests use HTTP fakes and do not require LAN access to the NVR; repeat the real smoke test only when the other machine can safely reach the device and there is a reason to revalidate it.
