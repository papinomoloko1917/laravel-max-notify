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

Phase 15 — MAX Messenger HTTP exploration is complete. Postman confirmed token authentication, image-upload URL creation, multipart JPEG upload, media-token extraction, chat-ID discovery through updates, and successful delivery of an image message to a real dialog.

Phase 16 — Complete, committed, and pushed through `4aa4954`. The MAX client creates authenticated image-upload URLs, uploads JPEG bytes as multipart data, and sends image messages to supplied chat IDs. All three boundaries are covered for success, HTTP failure, and malformed successful responses.

Phase 17 — The real sequential Dahua snapshot → MAX upload URL → multipart upload → message send flow has been observed successfully. One development run took 2,569 ms, proving that the slow external work should not hold the NVR webhook response open.

Phase 18 — Complete for the current database-queue design. An accepted webhook dispatches `ProcessCameraEvent`; successful and failed worker lifecycles have both been observed manually. The handler reloads current Camera/Client state, applies early exits, retrieves and uploads one snapshot, and reuses the media token for every assigned Client. MAX requests have explicit 3/10-second limits. The job has an 80-second timeout below `retry_after=90`, fails on timeout, and explicitly allows one attempt. Whole-job retries/backoff are deliberately absent because partial multi-client delivery is not idempotent.

Phase 19 — Complete as an explicit decision not to add Redis yet. The observed queue volume is low, PostgreSQL queue behavior is understood and sufficient, and the existing database cache supports atomic `Cache::add()` plus locks through the already migrated `cache` and `cache_locks` tables. Redis remains a future option if measured queue latency, throughput, or lock/cache contention justifies another service.

Phase 20 — Complete. After authentication, metadata validation, and notification-window acceptance, the webhook atomically claims a database-cache key derived from Camera ID, channel, and rule for five seconds. The first request dispatches normally; an identical request inside the TTL returns `200 Ignored`; distinct Cameras/channels/rules and requests after expiry remain independent.

Phase 21 — In progress. The minimal `camera_events` persistence layer is implemented and tested: an event retains the Camera name, channel, rule, processing status, aggregate recipient counters, optional failure type, and completion time. Deleting a Camera sets `camera_id` to null while preserving its historical events. The journal is not yet connected to the webhook or queued job.

## Current repository state

Checkpoint date: 2026-10-13.

- branch: `main`;
- committed baseline `main` matches `origin/main` at `c64d6b2` (`feat: suppress duplicate camera events`);
- the current uncommitted Phase 21 slice adds the `CameraEvent` model/factory/migration, the Camera relationship, and focused model tests; it does not yet change webhook or job behavior;
- commit `4085dee` publishes the used Sail 8.5/PostgreSQL Docker files and installs the official Russian Trusted Root/Sub CA certificates required for verified HTTPS to the current MAX endpoints;
- commit `63c01dd` records the completed synchronous observation checkpoint;
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
- commit `520b4eb` adds the minimal job/dispatch boundary and focused queue-fake assertions;
- commit `83c1982` injects the integration clients at execution time, reloads the active Camera with its current Clients, adds early exits for a missing/inactive Camera or no recipients, and covers those guards with focused tests;
- commit `e6c18da` starts the successful job path by retrieving one Dahua snapshot and requesting one MAX image-upload URL;
- commit `ec530e9` uploads the retrieved JPEG to the one-time MAX URL and stores the returned media token;
- commit `2bb1ea6` sends one MAX image message to the first already-loaded Client using that Client's `max_chat_id` and the uploaded media token;
- commit `4eee11f` iterates over all already-loaded Clients and sends one message per `max_chat_id`, while snapshot retrieval and image upload remain outside the loop; its focused test assigns two Clients and verifies one snapshot request, one upload-URL request, one multipart upload, and two message sends;
- commit `55d8471` adds environment-backed 3-second connection and 10-second overall limits to all three MAX request boundaries;
- the same commit gives `ProcessCameraEvent` an explicit 80-second timeout and marks it failed when that timeout is exceeded, remaining below the database connection's 90-second `retry_after`;
- on 2026-10-13, a controlled `Http::fake()` exception was processed through the real database worker with `--once --tries=1`: the job changed from `jobs=1`/`failed_jobs=0` to `jobs=0`/`failed_jobs=1` and appeared in `queue:failed`;
- the experimental Camera, Client, pivot row, and failed-job record were removed afterward; both queue tables returned to zero rows and no repository files changed;
- the current reviewed application change sets `ProcessCameraEvent::$tries` to `1`, making the single-attempt policy independent of worker CLI defaults;
- whole-job retry/backoff is deliberately postponed because retrying after partial multi-client delivery can duplicate earlier messages.
- commit `db89649` records the Phase 19 decision to retain PostgreSQL queue/cache and defer Redis;
- the current reviewed webhook change uses an atomic five-second database-cache claim before dispatch, returning the existing `200 Ignored` response for an identical Camera/channel/rule inside the TTL;
- focused tests cover immediate suppression, expiry, independent Camera/channel/rule keys, notification-window ordering, and the actual database cache store.
- focused CameraEvent model tests cover database defaults and casts, both relationship directions, and preservation of the event/name with a null Camera reference after Camera deletion.

## Phase 17 synchronous observation

- the first attempt failed at the Dahua step because local `DAHUA_BASE_URL` incorrectly included `/cgi-bin/`; keeping only the recorder origin restored the expected snapshot URL;
- the next attempt reached MAX but failed TLS verification because the current MAX certificate chain is issued by `Russian Trusted Sub CA`, which was absent from the standard Sail CA store;
- the official `Russian Trusted Root CA` and `Russian Trusted Sub CA` certificates were added to a published Sail 8.5 Docker build, without disabling TLS verification;
- a rebuilt container can establish verified HTTPS to `platform-api2.max.ru`;
- the controlled real chain completed successfully with a 49,502-byte JPEG and a returned MAX message ID;
- observed durations were 934 ms for the snapshot, 687 ms for upload-URL creation, 793 ms for image upload, and 155 ms for message send: 2,569 ms total;
- this was a development smoke observation, not webhook integration; the production webhook still responds without starting the external workflow.

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

Redis, Mailpit, and event history remain intentionally postponed. Laravel's database queue configuration and tables already exist from the Starter Kit. After authentication, metadata validation, notification-window acceptance, and a five-second atomic duplicate claim, `ProcessCameraEvent` is dispatched. Its handler receives `DahuaClient`/`MaxClient` from the container, performs current-state guards, retrieves one snapshot, requests one upload URL, uploads the JPEG once, and sends one image message to each currently assigned Client using the shared media token.

## Confirmed MAX Messenger behavior

Observed on 2026-10-10 through Postman against the real MAX API:

- requests use `https://platform-api2.max.ru` and pass the bot access token directly in the `Authorization` header without a `Bearer` prefix;
- `GET /me` returns `200 OK` with the authenticated bot object;
- a missing authorization header returns `401` with `verify.token` / `No access token`;
- `POST /uploads?type=image` returns an upload URL;
- a multipart `POST` to that URL with one JPEG in the `data` field returns the image token at `photos.0.token`;
- `GET /updates` exposed the real dialog identifier at `message.recipient.chat_id` for development-time discovery;
- `POST /messages?chat_id=<id>` with a JSON image attachment referencing the media token returned `200 OK` and delivered the text and image to the real dialog;
- malformed JSON returned `400` with `proto.payload`, while an invalid recipient returned `400` with `Unknown recipient`;
- bot tokens, upload URLs, media tokens, and recipient identifiers are intentionally excluded from the repository.

## Current MAX client slice

- environment-backed `services.max` configuration provides the MAX base URL and bot token without committing the real token;
- `App\Services\MaxClient::createImageUploadUrl()` sends authenticated `POST /uploads?type=image` requests and returns the validated response `url`;
- the client passes the raw access token in `Authorization` without a `Bearer` prefix and converts `4xx/5xx` responses through `Response::throw()`;
- a successful response with a missing or empty upload URL is rejected with `UnexpectedValueException`;
- focused tests prevent stray network access and cover the successful request/response contract, an HTTP `401`, and a malformed successful response;
- `App\Services\MaxClient::uploadImage()` sends JPEG bytes to the one-time upload URL as multipart field `data` with filename `snapshot.jpg` and returns the validated `photos.0.token` media token;
- focused multipart tests verify the exact upload URL, raw authorization header, file field/content/name, returned media token, HTTP failure propagation, and rejection of a successful response without the token;
- `App\Services\MaxClient::sendImageMessage()` sends JSON text plus an image media token to a supplied `chat_id` and returns the validated `message.body.mid` message ID;
- focused message tests verify the exact query parameter, raw authorization, JSON body, returned message ID, HTTP failure propagation, and rejection of a successful response without `mid`;
- all three MAX methods use environment-backed 3-second connection and 10-second overall request limits;
- the three MAX methods remain separate service boundaries and are composed with `DahuaClient` inside the queued job for all assigned Clients.

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
- current focused `artisan test tests/Feature/Models/CameraEventTest.php`: **3 passing tests and 13 assertions**;
- current focused `artisan test tests/Feature/Models/CameraTest.php` after adding webhook credentials: **5 passing tests and 10 assertions**;
- current focused `artisan test tests/Feature/Models/CameraTest.php` after adding notification-window storage: **6 passing tests and 13 assertions**;
- current focused `artisan test tests/Feature/DahuaWebhookTest.php`: **14 passing tests and 48 assertions**, including database-cache duplicate suppression, expiry, key independence, and notification-window ordering;
- current focused `artisan test tests/Feature/Jobs/ProcessCameraEventTest.php`: **4 passing tests and 7 assertions**, covering all three no-HTTP guards and reuse of one uploaded snapshot across two message recipients;
- current related job/webhook/Dahua/MAX regression: **27 passing tests and 48 assertions**;
- current focused `artisan test tests/Unit/NotificationWindowTest.php`: **10 passing tests and 10 assertions**;
- current `artisan test tests/Feature/CamerasTest.php`: **18 passing tests and 66 assertions**;
- current focused Camera/NotificationWindow/Dahua regression run after changing Pint: **38 passing tests and 98 assertions**;
- current focused `artisan test tests/Feature/Services/DahuaClientTest.php`: **4 passing tests and 5 assertions**;
- current focused `artisan test tests/Feature/Services/MaxClientTest.php`: **9 passing tests and 12 assertions**;
- current `artisan test tests/Feature/Services`: **13 passing tests and 17 assertions**;
- full `artisan test`: **113 tests, 324 assertions, 1 skipped and 1 risky**; the skip/risky result comes from the previously known Starter Kit Fortify feature guard, not this event-journal slice;
- full Pint with the intentional one-space concatenation configuration: **passes**;
- frontend production build: **passes**; Vite reports only the optional `fontaine` optimized-fallback notice;
- `git diff --check`: **passes**;
- Larastan reports only the **2 previously known Starter Kit-related findings**; adding the controller return type resolved the webhook finding, and no finding points to the Phase 12 code;
- previously known generated `lang/ru/*.php` formatting differences remain outside this block.

## Next exact learning block

Connect the new journal to one lifecycle boundary at a time. Start by deciding where an accepted webhook creates its `pending` event and how the resulting event ID reaches `ProcessCameraEvent`; cover that boundary before adding completion or failure transitions.

Do not run a real worker merely as an automated test: the current job delivers real notifications to every Client assigned to the Camera. Use a controlled Camera/Client setup when manually observing the worker.

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

The committed baseline through `c64d6b2` is pushed. The Phase 21 persistence slice is currently uncommitted and should be committed only after review. On the other machine after that checkpoint is pushed:

```bash
git pull
composer install
./vendor/bin/sail build --no-cache laravel.test
./vendor/bin/sail up -d
./vendor/bin/sail npm ci
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test tests/Feature/CamerasTest.php
./vendor/bin/sail artisan test tests/Unit/NotificationWindowTest.php
./vendor/bin/sail artisan test tests/Feature/Models/CameraTest.php
./vendor/bin/sail artisan test tests/Feature/DahuaWebhookTest.php
./vendor/bin/sail artisan test tests/Feature/Jobs/ProcessCameraEventTest.php
./vendor/bin/sail artisan test tests/Feature/Services/DahuaClientTest.php
./vendor/bin/sail artisan test tests/Feature/Services/MaxClientTest.php
```

Because `.env` is intentionally not committed, configure these values locally before clearing configuration:

- `APP_TIMEZONE=Europe/Moscow`;
- `DAHUA_BASE_URL`;
- `DAHUA_USERNAME`;
- `DAHUA_PASSWORD`;
- optionally override `DAHUA_CONNECT_TIMEOUT=3` and `DAHUA_TIMEOUT=10`;
- `MAX_BASE_URL=https://platform-api2.max.ru`;
- `MAX_TOKEN`;
- optionally override `MAX_CONNECT_TIMEOUT=3` and `MAX_TIMEOUT=10`.

`DAHUA_BASE_URL` must be only the recorder origin and must not include `/cgi-bin` or the snapshot path. Transfer real credentials through a secure channel, never Git or chat. Automated tests use HTTP fakes and do not require LAN access to the NVR or MAX; repeat the real smoke test only when the other machine can safely reach the device and there is a reason to revalidate it.
