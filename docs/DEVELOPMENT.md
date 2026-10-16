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

Phase 14 — The Dahua snapshot client is complete for the currently understood contract and has been generalized beyond its original single-NVR configuration. It now receives the selected Camera's base URL, Digest credentials, and channel. Automated tests cover success and representative failures, explicit request limits are configured, and real-device smoke checks confirmed Laravel/Guzzle Digest negotiation and JPEG retrieval.

Phase 15 — MAX Messenger HTTP exploration is complete. Postman confirmed token authentication, image-upload URL creation, multipart JPEG upload, media-token extraction, chat-ID discovery through updates, and successful delivery of an image message to a real dialog.

Phase 16 — Complete, committed, and pushed through `4aa4954`. The MAX client creates authenticated image-upload URLs, uploads JPEG bytes as multipart data, and sends image messages to supplied chat IDs. All three boundaries are covered for success, HTTP failure, and malformed successful responses.

Phase 17 — The real sequential Dahua snapshot → MAX upload URL → multipart upload → message send flow has been observed successfully. One development run took 2,569 ms, proving that the slow external work should not hold the NVR webhook response open.

Phase 18 — Complete for the current database-queue design. An accepted webhook dispatches `ProcessCameraEvent`; successful and failed worker lifecycles have both been observed manually. The handler reloads current Camera/Client state, applies early exits, retrieves and uploads one snapshot, and reuses the media token for every assigned Client. MAX requests have explicit 3/10-second limits. The job has an 80-second timeout below `retry_after=90`, fails on timeout, and explicitly allows one attempt. Whole-job retries/backoff are deliberately absent because partial multi-client delivery is not idempotent.

Phase 19 — Complete as an explicit decision not to add Redis yet. The observed queue volume is low, PostgreSQL queue behavior is understood and sufficient, and the existing database cache supports atomic `Cache::add()` plus locks through the already migrated `cache` and `cache_locks` tables. Redis remains a future option if measured queue latency, throughput, or lock/cache contention justifies another service.

Phase 20 — Complete. After authentication, metadata validation, and notification-window acceptance, the webhook atomically claims a database-cache key derived from Camera ID, channel, and rule for five seconds. The first request dispatches normally; an identical request inside the TTL returns `200 Ignored`; distinct Cameras/channels/rules and requests after expiry remain independent.

Phase 21 — Complete for the current server-side journal design. The minimal `camera_events` persistence layer retains the Camera name, channel, rule, processing status, aggregate recipient counters, optional failure type, and completion time. Accepted non-duplicate webhooks create `pending` rows and dispatch their IDs. The job records explicit `skipped` outcomes, successful `completed` delivery with recipient totals, and a terminal `failed` callback that stores only the safe exception class while preserving partial progress. Deleting a Camera nulls its foreign key without erasing history.

Phase 22 — Complete for the current requirements. Event history provides newest-first pagination, lifecycle filtering with page reset, context-sensitive empty states, safe details that survive Camera deletion, and a confirmed action that removes terminal history without touching pending work. Laravel Scheduler runs `camera-events:prune` daily at 03:00 and retains processed events for 30 days by default. The Dashboard provides Camera, active-Camera, Client, per-status event, and queue counts plus the five newest events. No charts, caching, or retry controls were introduced.

Phase 23 — Deferred because no new email workflow currently requires Mailpit.

Phase 24 — Complete for the current application. Authorization uses a closed-registration, single-role administration model. The Dahua webhook has a configurable two-dimensional abuse limiter separate from business duplicate suppression. Three PostgreSQL indexes cover only demonstrated status/FK/reverse-pivot paths, and Larastan is a clean project gate. The authenticated Dashboard exposes read-only queue totals without payloads, exception text, or retry controls. Regression tests protect the accepted-webhook log whitelist, secret-free serialized job payload, safe CameraEvent failure metadata, and Dashboard boundary. The operator runbook defines protected investigation, 30-day failed-job retention, individual cleanup, and the prohibition on unsafe whole-job retry. Deployment-specific workers, scheduled pruning, backups, and restore belong to Phase 25.

Phase 25 — Production baseline implemented and locally verified. The deployment target remains the dedicated MiniPC MA01 with Intel N150, 16 GB RAM, 512 GB storage, Ubuntu Server 26.04.1 LTS, and a LAN connection to Dahua without a public domain. The dedicated production topology uses Caddy + immutable PHP-FPM app + one database queue worker + one scheduler + internal PostgreSQL. Caddy provides private-CA HTTPS for administration and an HTTP listener restricted at the application-proxy boundary to the log-suppressed webhook path. The repository now includes a production environment template, separate Compose/Docker files, an interactive administrator-creation command, and a detailed Russian installation runbook. Real MiniPC installation, fixed-address firewall rules, off-device backup automation/restore rehearsal, UPS behavior, and monitoring remain open site-specific work.

Phase 26 — Implemented in the current working tree. One password-confirmed settings page validates a replacement MAX token through `GET /me`, stores it encrypted in the singleton bot record, and exposes only safe bot identity. Each Camera owns a snapshot device origin, Digest login/encrypted password, channel, multiple exact rule-code/title mappings, an encrypted/rotatable URL webhook token, and optional Basic webhook credentials. Common event types are selected from a prepared catalog, while custom identifiers remain available under a URL-safe validation rule. Each saved rule renders a complete copyable token URL. Stored secrets are hidden from serialization; authentication failures advertise a standards-compliant Basic challenge and emit safe reason/scheme diagnostics without credential values. The webhook validates configured channel ownership and snapshots the resolved title into `CameraEvent` with a raw-code fallback; the worker resolves the selected Camera's snapshot connection and sends a deterministic title, Camera location/name, and accepted-event time together with the image.

## Current repository state

Checkpoint date: 2026-10-16.

- branch: `main`;
- committed `main` matches `origin/main` at `4afd010` (`docs: define production deployment target`);
- the current uncommitted working tree combines the complete Phase 26 MAX-bot/rule/message/webhook-token slice with the production deployment baseline, the Russian README, and the aligned `vite-plus`/Vite core `0.3.3` dependency update;
- commit `4ad0ee6` removes the ineffective `verified` middleware while retaining `auth` and `password.confirm`, and documents the closed-registration single-role administration model with focused tests;
- commit `1f05e9d` switches project collaboration to agent-led development; commits and pushes remain user-controlled unless explicitly requested;
- commit `148fe7a` adds configurable per-username and aggregate-IP webhook limits with focused coverage and no change to accepted-event behavior;
- commit `0b243cc` adds one reversible migration and a PostgreSQL schema test for the three query/FK indexes justified by current application behavior;
- commit `7705580` restores a clean Larastan gate with typed CameraEvent relationships, local profile validation, and removal of unsupported two-factor factory/component/test/provider remnants; password settings behavior is unchanged;
- commit `45b1958` adds read-only queued/failed job totals to the Dashboard using the database queue table and connection names from Laravel configuration;
- commit `bb1bc2d` adds regression guarantees that explicit webhook logs contain only approved context, serialized jobs contain no integration credentials, `camera_events` retain only exception classes, and the Dashboard never renders queue payloads or exception text;
- commit `aa9f8ca` adds the trusted-operator runbook, selects 30-day failed-job retention, documents investigation/individual cleanup, explicitly prohibits unsafe retry and closes Phase 24 for the current application;
- commit `4afd010` records the confirmed MiniPC/Ubuntu/LAN target, selects a production Docker topology distinct from Sail, identifies the HTTPS/webhook split and off-device backup boundary, and lists the network/backup inputs still required before configuration files are created;
- commit `d6a1511` adds the `CameraEvent` model/factory/migration, the Camera relationship, focused model tests, and synchronized Phase 21 documentation; it does not change webhook or job behavior;
- the current reviewed controller change persists Camera ID/name and validated channel/rule after duplicate suppression, then dispatches the job with the saved event ID;
- the current test change creates a related `CameraEvent` for each direct `ProcessCameraEvent` scenario and supplies its ID through the new constructor argument;
- webhook coverage verifies the complete initial journal row, matches its ID to the queued job, keeps one row for an immediate duplicate, and stores no row for an outside-window request;
- the current job change loads the correlated event, records three safe `skipped` reasons, stores the intended recipient total before external I/O, increments the sent count after each successful message, and marks fully delivered events `completed`;
- the current failure callback safely ignores a missing journal row, records `failed` with the exception class or `unknown`, sets `processed_at`, and leaves any accumulated recipient counters unchanged;
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

Redis and Mailpit remain intentionally postponed. Laravel's database queue configuration and tables already exist from the Starter Kit. After authentication, metadata validation, notification-window acceptance, and a five-second atomic duplicate claim, the webhook snapshots the configured per-Camera rule title and dispatches `ProcessCameraEvent`. Its handler receives the integration clients and message formatter from the container, performs current-state guards, retrieves one snapshot, requests one upload URL, uploads the JPEG once, and sends one formatted text-and-image message to each currently assigned Client using the shared media token. The event acceptance time, not worker execution time, appears in that message. The resulting operational history is visible through the authenticated read-only event page.

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

- environment-backed `services.max` configuration provides the MAX base URL and initial/fallback token without committing the real token;
- `App\Models\MaxBotSetting` holds exactly one active bot record; its token uses Laravel's encrypted cast, is hidden from serialization, and takes precedence over `MAX_TOKEN`;
- the password-confirmed Livewire settings page validates candidate tokens with `App\Services\MaxClient::inspectBot()` before replacing the current bot and never hydrates the stored token into the form;
- `App\Services\MaxClient::createImageUploadUrl()` sends authenticated `POST /uploads?type=image` requests and returns the validated response `url`;
- the client passes the raw access token in `Authorization` without a `Bearer` prefix and converts `4xx/5xx` responses through `Response::throw()`;
- a successful response with a missing or empty upload URL is rejected with `UnexpectedValueException`;
- focused tests prevent stray network access and cover the successful request/response contract, an HTTP `401`, and a malformed successful response;
- `App\Services\MaxClient::uploadImage()` sends JPEG bytes to the one-time upload URL as multipart field `data` with filename `snapshot.jpg` and returns the validated `photos.0.token` media token;
- focused multipart tests verify the exact upload URL, raw authorization header, file field/content/name, returned media token, HTTP failure propagation, and rejection of a successful response without the token;
- `App\Services\MaxClient::sendImageMessage()` sends JSON text plus an image media token to a supplied `chat_id` and returns the validated `message.body.mid` message ID;
- focused message tests verify the exact query parameter, raw authorization, JSON body, returned message ID, HTTP failure propagation, and rejection of a successful response without `mid`;
- all MAX methods use environment-backed 3-second connection and 10-second overall request limits;
- the MAX HTTP methods remain separate service boundaries and are composed with `DahuaClient` inside the queued job for all assigned Clients.

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

- each Camera stores its own HTTP(S) device origin, Digest username, encrypted/hidden Digest password, and channel; global Dahua configuration now contains request limits only;
- `App\Services\DahuaClient::snapshot(string $baseUrl, string $username, string $password, int $channel)` performs a Digest-configured GET to `/cgi-bin/snapshot.cgi` and passes the channel as a query parameter;
- the administration form requires an origin without path/query, never renders the stored password, and preserves it when the replacement field is empty;
- `(snapshot_base_url, channel)` is unique, allowing different devices to reuse channel numbers;
- the successful response body is returned as raw string bytes;
- a focused HTTP-fake test prevents stray network requests, verifies the returned JPEG bytes, and verifies the exact GET URL for channel 13;
- `4xx/5xx` responses are converted to Laravel `RequestException` through `Response::throw()` before content validation;
- a successful response whose `Content-Type` is not exactly `image/jpeg` is rejected with `UnexpectedValueException` rather than returned as snapshot bytes;
- environment-backed defaults limit connection establishment to 3 seconds and the overall request to 10 seconds;
- a focused failed-connection fake verifies that Laravel `ConnectionException` propagates from the client;
- focused verification: **4 passing tests and 5 assertions**, with Pint passing for the client, test, and service configuration;
- full verification after this slice: **93 tests, 266 assertions, 1 skipped and the same 1 known risky Starter Kit test**; full Pint passes;
- real-device Tinker smoke checks on 2026-10-10 and after the per-Camera migration on 2026-10-15 successfully retrieved a JPEG through this client; the latest response was 233,246 bytes and began with the JPEG `FF D8` signature;
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
- current focused `artisan test tests/Feature/DahuaWebhookTest.php`: **19 passing tests and 85 assertions**, including authentication/validation, safe rejection diagnostics, notification windows, duplicate suppression, journal/queue correlation, rate limiting, exact safe logging context, and a real database-queue payload free of webhook/Dahua/MAX credentials;
- current focused `artisan test tests/Feature/Jobs/ProcessCameraEventTest.php`: **7 passing tests and 38 assertions**, covering all three persisted `skipped` outcomes, a two-recipient `completed` outcome, safe exception/unknown failure metadata without exception messages, preserved partial counters, and a missing journal row;
- current related job/webhook/Dahua/MAX regression: **27 passing tests and 48 assertions**;
- current focused `artisan test tests/Unit/NotificationWindowTest.php`: **10 passing tests and 10 assertions**;
- current `artisan test tests/Feature/CamerasTest.php`: **18 passing tests and 66 assertions**;
- current focused Camera/NotificationWindow/Dahua regression run after changing Pint: **38 passing tests and 98 assertions**;
- current focused `artisan test tests/Feature/Services/DahuaClientTest.php`: **4 passing tests and 5 assertions**;
- current focused `artisan test tests/Feature/Services/MaxClientTest.php`: **9 passing tests and 12 assertions**;
- current `artisan test tests/Feature/Services`: **13 passing tests and 17 assertions**;
- focused event-history/retention/index regression: **13 passing tests and 70 assertions**, covering access, rendering/filtering, safe details, confirmed terminal-history cleanup, pending preservation, 30-day pruning, invalid retention protection, and the retention index;
- focused `artisan test tests/Feature/DashboardTest.php`: **5 passing tests and 22 assertions**, covering guest rejection, authenticated access, exact domain and queue counts, non-disclosure of queue payload/exception text, five-event newest-first limiting, and the empty state;
- focused `artisan test tests/Feature/AdminAccessTest.php`: **3 passing tests and 13 assertions**, covering disabled public registration, guest rejection from all administration pages, and the intentional single-role access model without email verification;
- focused `artisan test tests/Feature/DatabaseIndexesTest.php`: **1 passing test and 3 assertions**, verifying the exact PostgreSQL index column sequences;
- related index/model/Client/event-history regression: **29 passing tests and 154 assertions**;
- sensitive-data regression across webhook, Dashboard, and job tests: **30 passing tests and 133 assertions**;
- Phase 26 focused bot settings/token/client/job set: **26 passing tests and 85 assertions**;
- Phase 26 focused Camera-rule/webhook/job/event/message set: **40 passing tests and 179 assertions**;
- prepared-event/channel focused regression: **51 passing tests and 188 assertions**;
- current Camera UI regression: **22 passing tests and 96 assertions**, including localized webhook credential labels and validation;
- current Camera/webhook authentication regression: **41 passing tests and 179 assertions**;
- per-Camera snapshot connection regression across Camera UI/model, Dahua client, webhook, and job tests: **62 passing tests and 262 assertions**;
- Camera-rule coverage verifies that generated webhook URLs use configured `DAHUA_WEBHOOK_BASE_URL` rather than `APP_URL` or the current browser host, contain the selected Camera's encrypted-at-rest token, support explicit rotation, and present optional Basic Username separately without disclosing the password;
- full Sail `composer test`: Pint passes, Larastan reports **0 errors**, and Pest reports **170 passing tests and 647 assertions**, with no skipped or risky tests;
- `schedule:list` registers `camera-events:prune` daily at **03:00**, and both development `queue` and `scheduler` Compose services are running;
- current combined webhook/job run: **18 passing tests and 79 assertions**;
- current CameraEvent model/webhook/job regression: **24 passing tests and 102 assertions**;
- targeted Pint for the controller and job test: **passes**;
- full Pint with the intentional one-space concatenation configuration: **passes**;
- frontend production build: **passes** after aligning `vite-plus` and its Vite core alias at `0.3.3`; `npm audit` reports **0 vulnerabilities**, and the only build notice concerns optional `fontaine` optimized fallbacks;
- production Compose interpolation: **passes** against `.env.production.example`;
- production PHP 8.5 and Caddy images: **build successfully** from a clean multi-stage path; compiled Flux/Tailwind assets and required PHP extensions are present;
- Caddy production configuration: **valid** for a representative LAN IP, including private-CA HTTPS, HTTP webhook-only routing, and webhook access-log suppression;
- disposable production runtime smoke check: PostgreSQL became healthy, all **17 migrations** applied to a fresh volume, `artisan optimize` completed, the queue worker remained running after schema creation, and Scheduler registered `camera-events:prune`; the isolated test containers and volumes were then removed;
- PHP-FPM configuration check: **passes**, with its request access log explicitly disabled so webhook query tokens cannot bypass the Caddy logging boundary;
- production image command discovery exposes `admin:create`; its focused tests cover verified-user creation, hashed secret input, duplicate email, and weak-password rejection;
- `git diff --check`: **passes**;
- `composer types:check`: **passes with 0 errors**;
- previously known generated `lang/ru/*.php` formatting differences remain outside this block.

## Next exact development block

The newest Camera-channel migration is applied on this machine, and local `APP_URL` uses the confirmed Camera-reachable address `http://10.10.0.141`. The local `laravel.test` image was rebuilt on 2026-10-15 after the MAX-token check exposed a stale container without the committed Russian Trusted Root/Sub certificates. A verified HTTPS request from the rebuilt container reaches `GET https://platform-api2.max.ru/me`, and the real MAX bot is now stored in the singleton settings record.

Real authenticated webhook requests are accepted and create event-history rows. Development Compose keeps both the database worker and Laravel Scheduler running. The current Camera `#3` retrieves channel `1` directly from `http://10.10.0.181`; a post-credential-update smoke request returned a valid 327,567-byte JPEG. Two earlier jobs failed with Dahua `401 Invalid Authority` before those Digest credentials were corrected and must not be retried. A real request observation on 2026-10-15 proved that the tested direct Camera stored its HTTP-action username/password but sent four webhook attempts with no Authorization header. The token migration is applied locally, Camera `#3` has an encrypted/hidden 64-character token, and its generated rule URL is ready to be copied into the device with device-side Authentication disabled.

The production baseline is now ready for a controlled installation rehearsal. Before installing on the MiniPC, commit and push the complete Phase 26/deployment working tree, choose its fixed LAN IP, and fill `.env.production` from the committed example. During the rehearsal follow `docs/DEPLOYMENT.md`, import the Caddy root certificate on an administrator device, run one controlled token-authenticated event, and record the real firewall source addresses. Do not treat the installation as recoverable until an off-device PostgreSQL dump and `.env.production`/`APP_KEY` recovery copy have been created and restored in a test environment.

The clean-server runbook was rechecked on 2026-10-16 against the current official Docker Engine Ubuntu instructions. It now uses Docker's deb822 `docker.sources` repository format, exports Caddy's public local CA outside the Git checkout, and includes a queue-container MAX TLS probe. The latter explicitly guards against the stale-image failure observed in development, where accepted events reached the worker but every MAX upload request failed with `cURL error 60` until the Sail image and containers were rebuilt.

Do not run a real worker merely as an automated test: the current job delivers real notifications to every Client assigned to the Camera. Use a controlled Camera/Client setup when manually observing the worker.

## Decisions to preserve

- Camera ↔ Client is many-to-many through `camera_client`; duplicate pairs are forbidden and deleting either model removes only its pivot rows.
- one installation uses exactly one active MAX bot; its encrypted database token takes precedence over the environment fallback, and `APP_KEY` is part of recoverable production state;
- rule codes are exact and unique per Camera; unknown codes remain deliverable through a raw-code title fallback, while accepted events retain the resolved title snapshot;
- a Camera owns its snapshot device connection; channel uniqueness is scoped to the device origin, and application-generated webhook URLs still must be copied into the device's event action;
- `max_chat_id` currently uses PostgreSQL `bigint` and a PHP integer cast; this remains provisional until the MAX API contract is confirmed.
- Client list Camera totals are calculated by PostgreSQL through `withCount('cameras')`.
- successful Client creation resets its fields but intentionally leaves the creation modal open.
- edit and create fields use separate Livewire state.
- Client update validation must not reject the unchanged MAX chat ID belonging to the selected Client.
- failed Client update validation must preserve the record and keep the edit modal open for correction.
- the Dahua webhook will use normal Laravel HTTP routing, not Livewire.
- external API calls do not belong in Livewire components or Eloquent models.
- Redis and queues are introduced only when a concrete requirement appears.
- development is agent-led by default: Codex implements coherent requested blocks with tests and documentation, while commits and pushes remain user-controlled unless explicitly requested.

## Moving to another machine

Committed `main` still ends at `4afd010`. The current working tree combines the complete Phase 26 bot/rule/message/webhook-token work with the production deployment baseline; it must be reviewed, committed, and pushed before another machine or the clean server can receive it. The site-specific IPs, firewall scope, off-device backup destination, and UPS decision intentionally remain outside committed secrets/configuration.

On the other machine:

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
./vendor/bin/sail artisan test tests/Feature/Models/CameraEventTest.php
./vendor/bin/sail artisan test tests/Feature/DahuaWebhookTest.php
./vendor/bin/sail artisan test tests/Feature/Jobs/ProcessCameraEventTest.php
./vendor/bin/sail artisan test tests/Feature/Services/DahuaClientTest.php
./vendor/bin/sail artisan test tests/Feature/Services/MaxClientTest.php
./vendor/bin/sail artisan test tests/Feature/Settings/MaxBotSettingsTest.php
./vendor/bin/sail artisan test tests/Feature/CameraRulesTest.php
```

Because `.env` is intentionally not committed, configure these values locally before clearing configuration:

- `APP_TIMEZONE=Europe/Moscow`;
- `DAHUA_WEBHOOK_BASE_URL` as the device-reachable webhook origin (it may differ from the HTTPS `APP_URL` in production);
- optionally override `DAHUA_CONNECT_TIMEOUT=3` and `DAHUA_TIMEOUT=10`;
- optionally override `DAHUA_WEBHOOK_USERNAME_LIMIT_PER_MINUTE=60` and `DAHUA_WEBHOOK_IP_LIMIT_PER_MINUTE=300`;
- optionally override `CAMERA_EVENT_RETENTION_DAYS=30` with a positive integer;
- `MAX_BASE_URL=https://platform-api2.max.ru`;
- `MAX_TOKEN` as the initial/fallback value until a validated database token is saved;
- optionally override `MAX_CONNECT_TIMEOUT=3` and `MAX_TIMEOUT=10`.

Configure each Camera's snapshot origin and Digest credentials through the authenticated administration page. The origin must not include `/cgi-bin`, the snapshot path, or query parameters. Transfer real credentials through a secure channel, never Git or chat, and preserve the same `APP_KEY` when moving/restoring the database because encrypted snapshot passwords depend on it. Automated tests use HTTP fakes and do not require LAN access to the NVR or MAX; repeat the real smoke test only when the other machine can safely reach the device and there is a reason to revalidate it.
