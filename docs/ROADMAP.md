# max-notify — Learning & Development Roadmap

This roadmap is incremental. It describes direction, not a checklist that must be implemented immediately.

Rule:

understand the problem
→ learn one concept
→ implement a coherent block of related tasks
→ review
→ test
→ continue

## Current progress

As of 2026-10-16:

- Phases 0–6 are complete, committed, and pushed.
- Phase 7 is complete and committed: the application has a protected Cameras Livewire page, navigation entry, custom Flux-compatible icon, and access tests.
- Phase 8 is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema: listing, creation, filtering, pagination, editing, and confirmed deletion are implemented and covered.
- Phase 9 is complete, committed, and pushed through `da1aca1`: the Client list, creation, editing, validation, confirmed deletion, and paginated Camera assignment editing with validated `sync()` persistence are implemented and covered.
- Phase 10 exploration is complete against a real Dahua NVR using IVS: the callback is a `GET` with an empty body, static application-defined query metadata, minimal headers, and an unusable Docker-translated source IP. Two calls eight seconds apart matched separate triggers under five-second anti-dither, with no duplicate observed for one controlled trigger. The NVR sends HTTP Basic credentials preemptively when Authentication is enabled.
- Phase 11 is complete: the normal controller endpoint authenticates active Cameras, uniformly rejects credential failures, validates static IVS metadata, logs only safe accepted-event context, and returns explicit plain-text responses. Both Postman and the real NVR successfully reached `/webhooks/dahua` with `event=ivs`, `channel=7`, and `rule=perimeter`; the temporary diagnostic route has been removed.
- Phase 12 is complete, committed, and pushed through `04fdb86`: nullable PostgreSQL time fields store one per-Camera window, `APP_TIMEZONE=Europe/Moscow` makes the installation clock explicit, the isolated rule covers daytime/overnight/boundary cases, the webhook returns `200 Ignored` outside the window, and the Camera UI loads, saves, clears, and cross-validates the window.
- Phase 13 is complete: the real NVR accepts Digest-authenticated `GET /cgi-bin/snapshot.cgi?channel=<channel>` requests, and a valid channel returns `200 OK` with an `image/jpeg` response in both a browser and Postman. A wrong password produces `401 Unauthorized` with a text error, while a nonexistent channel produces `400 Bad Request` with a different text error. The disclosed exploratory credential was rotated.
- Phase 14 is complete and later generalized beyond its original single-NVR assumption: the Digest snapshot client accepts the selected Camera's device origin, credentials, and channel while retaining environment-backed 3/10-second limits. `Http::fake()` covers successful JPEG bytes, HTTP errors, invalid content types, and connection failures; real-device smoke checks confirmed JPEG data beginning with `FF D8`.
- Phase 15 is complete: Postman confirmed raw `Authorization` token authentication against `platform-api2.max.ru`, image-upload URL creation, multipart JPEG upload, media-token extraction from `photos.0.token`, chat-ID discovery through `GET /updates`, and successful delivery through `POST /messages`. Representative missing-authentication, malformed-body, and unknown-recipient failures were also observed.
- Phase 16 is complete and pushed through `4aa4954`: the environment-backed `MaxClient` creates upload URLs, uploads JPEG bytes as multipart field `data`, and sends text plus the resulting media token to a supplied chat ID. `Http::fake()` verifies exact authentication, URLs/query parameters, multipart and JSON payloads, returned identifiers, HTTP error propagation, and malformed-response rejection.
- Phase 17 is complete: a controlled real Dahua snapshot → MAX upload URL → multipart upload → message send run delivered a 49,502-byte JPEG and returned a message ID in 2,569 ms. The measured external latency and independent failure points justify moving this work outside the webhook response. Verified MAX HTTPS currently requires the Russian Trusted Root/Sub CA chain in the Sail image; TLS verification remains enabled.
- Phase 18 is complete for the current database-queue design: accepted webhooks dispatch `ProcessCameraEvent`; focused tests cover dispatch, guards, and multi-recipient delivery; manual worker observations confirmed both successful removal and `failed_jobs` storage without external network access. Development Compose now keeps one database worker running so accepted events do not remain pending. The job reloads current state, retrieves/uploads one snapshot, reuses its token for every recipient, limits MAX requests to 3/10 seconds, times out at 80 seconds below `retry_after=90`, fails on timeout, and explicitly permits one attempt. Whole-job retry/backoff is deliberately postponed because partial multi-client delivery can duplicate notifications.
- Phase 19 is complete as a deliberate deferral: the working PostgreSQL queue is sufficient at current volume, while the existing database cache already provides atomic `Cache::add()` and locks for the upcoming duplicate-protection slice. Redis remains available later if measured latency, throughput, or contention provides a concrete reason for another service.
- Phase 20 is complete: after authentication, validation, and notification-window acceptance, the webhook atomically claims a five-second database-cache key derived from Camera ID, validated channel, and rule. Immediate identical requests return `200 Ignored` without dispatch; expiry and independent key dimensions are covered, including one test against the actual database store.
- Phase 21 is complete for the current server-side journal: accepted non-duplicate webhooks persist `pending` events and dispatch their IDs; the job records explicit `skipped`, successful `completed`, and terminal `failed` outcomes while preserving aggregate and partial recipient counts. Focused tests cover persistence, correlation, guard outcomes, complete delivery, safe failure metadata, and missing journal rows.
- Phase 22 is complete for current requirements: event history provides pagination, lifecycle filtering/reset, contextual empty states, safe details, a confirmed processed-history cleanup action, and scheduled 30-day terminal-event retention that never removes pending work. Dashboard provides live Camera/Client/status counts and the five newest events without speculative charts or caching.
- Phase 23 remains deferred because there is no new email workflow requiring Mailpit.
- Phase 24 is complete for the current application: authorization uses a closed-registration single-role model; webhook abuse limits and justified PostgreSQL indexes are in place; Larastan is clean; queue/log/secret disclosure boundaries have regression coverage; and the trusted-operator runbook defines 30-day failed-job retention, investigation, individual cleanup, and the prohibition on unsafe whole-job retry. Deployment-specific worker supervision, scheduled pruning, backups, and restore move to Phase 25.
- Phase 26 is complete: the installation has one administrator-replaceable MAX bot whose token is validated through `GET /me` and encrypted in PostgreSQL; each Camera has its own device origin, encrypted Digest snapshot password, channel, encrypted/rotatable webhook URL token, optional Basic webhook credentials, and multiple exact webhook rule mappings; common event types create those mappings without manual codes and expose complete copyable webhook URLs; accepted events snapshot the resolved title; and queued delivery sends a formatted title/location/acceptance-time message with the image. Existing secrets are hidden from model serialization, authentication failures provide the standard Basic challenge, and unknown rule codes remain deliverable by falling back to the raw code.
- Phase 27 tunes measured notification latency without adding infrastructure: the database worker polling interval is configurable and defaults to one second, while MAX messages show webhook acceptance time with second precision. Redis and additional workers remain deferred until backlog or concurrency measurements justify them.
- Phase 28 captures one event JPEG synchronously before queue handoff, keeps it on the private shared storage volume, uploads that exact early frame from the worker, and deletes it on every job termination path. This intentionally trades roughly one LAN snapshot request of webhook response time for a much lower risk that fast transport has left the scene.
- Phase 25 now has an implemented and locally build-verified production baseline: a separate immutable PHP 8.5 image, Caddy HTTPS administration and log-suppressed HTTP webhook listeners, one worker, one scheduler, internal PostgreSQL, an interactive administrator-creation command, production environment template, and a detailed Russian installation procedure. Real MiniPC installation, source-IP firewall rules, off-device backup automation/restore rehearsal, UPS decision, and monitoring remain site-specific work.
- Redis remains deliberately deferred; PostgreSQL continues to support the current queue, cache, duplicate protection, and event journal volume.

This section records progress only. Later phases remain direction, not requirements for the current branch.

## Phase 0 — Inspect the fresh Laravel project

Learn the Laravel directory structure, `composer.json`, `package.json`, `.env` vs `.env.example`, Starter Kit contents, and current `compose.yaml`.

Exit: we know exactly what the installer created.

## Phase 1 — Git and Sail workflow

Learn Git workflow for three machines and Sail basics.

Exit: the application starts through Sail and setup is documented.

## Phase 2 — PostgreSQL connection

Learn Laravel DB configuration, PostgreSQL service names, migrations, and rollback.

Exit: Laravel connects to PostgreSQL and migrations work.

Adminer may be added here if a visual DB tool would help. It is optional.

## Phase 3 — Understand the domain

Before creating tables, describe the concepts in plain language:

- User;
- Camera;
- Client;
- CameraEvent as a future possibility.

Resolve only what is needed for the first model.

## Phase 4 — First Camera migration

Learn migrations, PostgreSQL column types, nullable/default, indexes/unique constraints, timestamps.

Exit: minimal useful Camera schema with a reason for every column.

## Phase 5 — Camera Eloquent model

Learn Eloquent, mass assignment, casts, factories, and a small DB test.

## Phase 6 — Clients and relationships

Learn foreign keys, pivot tables, and many-to-many Eloquent relationships.

Likely relation: Camera ↔ Client = many-to-many.

## Phase 7 — Authentication and admin shell

Learn the Livewire Starter Kit, protected routes, Livewire pages, and Flux UI basics.

## Phase 8 — Camera management UI

Learn Livewire state, validation, forms, Flux UI, and secure camera credential handling.

## Phase 9 — Client management UI

Learn relationship editing, Livewire forms, and validation.

## Phase 10 — Dahua webhook with HTTP/Postman

Learn HTTP method, URL, query parameters, headers, body, status codes, and machine-to-machine requests.

Workflow:

Dahua docs/real behavior
→ Postman request
→ understand payload
→ Laravel endpoint
→ Pest feature test

Exit: we can manually reproduce a representative Dahua request.

## Phase 11 — Minimal synchronous webhook

Learn routing, controller responsibility, validation, authentication/shared secrets, logging, and feature tests.

Do not introduce queues yet.

## Phase 12 — Event filtering and time windows

Learn business rules, testable services/actions, and time-window edge cases.

## Phase 13 — Explore Dahua HTTP API

Learn outgoing HTTP requests, digest auth, timeouts, snapshot endpoint, and Postman where useful.

## Phase 14 — Dahua client + `Http::fake()`

Learn Laravel HTTP Client, service boundaries, fake responses, and exceptions.

## Phase 15 — Explore MAX Messenger API

Learn authentication, upload workflow, message sending, and request/response inspection in Postman.

## Phase 16 — MAX client + `Http::fake()`

Learn multi-step external API workflows, uploads, error handling, and fake responses.

## Phase 17 — Observe synchronous limitations

Intentionally observe the cost of:

webhook → snapshot → MAX upload → MAX send → response

Exit: explain why background processing is useful.

## Phase 18 — Laravel Queue

Learn jobs, dispatch, workers, retries, timeouts, failed jobs, and idempotency.

Start with Laravel's already configured database queue so the job lifecycle is visible without adding infrastructure. Choose a different backend only after its benefit is understood.

## Phase 19 — Redis

Evaluate Redis against the now-understood queue use-case and introduce it if its operational benefits justify another service.

Learn Redis basics, queue backend, Sail service, and Laravel configuration.

## Phase 20 — Duplicate-event protection

Learn Cache, TTL, atomic operations, and race conditions.

Use Redis if it fits the proven requirement.

## Phase 21 — Camera event journal

Design persistence only after we know which event-processing information is useful.

Learn statuses, logs vs operational history, and indexes.

Current exit: the server-side journal records pending, skipped, completed, and failed outcomes with safe metadata and aggregate delivery progress.

## Phase 22 — Dashboard and event UI

Learn Livewire filtering/pagination and Flux tables/badges/modals.

Current exit: the protected event page has a newest-first paginated table, translated lifecycle filters/badges, delivery progress, contextual empty states, and safe read-only details. The protected Livewire Dashboard shows current Camera, active-Camera, Client, and lifecycle-status counts plus the five newest events and a journal link. Focused tests cover both surfaces.

## Phase 23 — Email only if needed

If password reset, verification, or email notifications are actually used, introduce Mailpit for local development.

Do not add it merely because Laravel supports mail.

## Phase 24 — Hardening

Authorization, secrets, encrypted fields, rate limiting where justified, queue recovery, indexes, logs, backups.

Current state: complete for the current application. Public registration is disabled, every provisioned authenticated User is intentionally an administrator, and email verification is not misrepresented as authorization. Unsupported two-factor Starter Kit remnants have been removed because the feature and its database columns are absent. The Dahua route applies independent per-presented-username and source-IP abuse budgets before controller authentication. Concrete event-status, event-FK, and reverse Client-pivot queries are indexed; low-selectivity or currently tiny lookup paths remain unindexed until measurements justify them. Larastan passes with no ignored findings. The Dashboard reads configured database queue tables to show queued and failed totals without exposing payloads, exceptions, or retry actions. Tests verify that the accepted webhook log uses an exact safe context, queued jobs serialize no credentials, and application-owned CameraEvent history stores no exception messages. `docs/OPERATIONS.md` defines protected investigation, 30-day retention, individual cleanup, and the current no-retry rule.

## Phase 25 — Deployment preparation

Production environment, queue worker lifecycle, HTTPS/reverse proxy basics, persistence, backups, and migrations.

Current state: the repository contains a separate production Compose topology rather than reusing Sail. It builds an immutable PHP 8.5/compiled-frontend image, serves the administration UI through Caddy private-CA HTTPS, limits the HTTP listener to the log-suppressed Dahua webhook path, runs one database worker and one scheduler, and keeps PostgreSQL internal and persistent. A production environment template, secure interactive administrator creation, and a detailed Russian installation/update/check procedure are included and locally build-validated.

Remaining exit work is site-specific: perform the real MiniPC installation, record fixed server/NVR addresses and administrator scope, persist tested Docker firewall rules, choose and automate an off-device backup with a restore rehearsal, decide UPS behavior, and add operational monitoring.

## Phase 26 — Configurable MAX bot and event messages

Support the confirmed product model without introducing multi-tenancy:

- exactly one active MAX bot per installation;
- password-confirmed bot replacement after `GET /me` validation;
- encrypted token storage with `MAX_TOKEN` retained as an initial/fallback value;
- multiple exact `rule` mappings per Camera;
- an encrypted and rotatable per-Camera webhook URL token for devices that do not send configured Basic credentials, while retaining Basic compatibility;
- one snapshot device origin, encrypted Digest credential set, and channel per Camera, with uniqueness scoped to the device origin and a nullable migration path for legacy records;
- a prepared event catalog plus URL-safe custom events and generated copyable webhook URLs;
- event-time snapshots of the readable title;
- a deterministic text-and-image notification containing title, Camera name, and accepted-event time in Moscow time.

Current state: implemented and covered. A controlled real MAX/NVR smoke observation remains appropriate after migrations and administrator configuration; it is not an automated test because it produces a real recipient notification.

## Phase 27 — Notification latency observation and tuning

Measure the existing flow before changing infrastructure, remove avoidable application delay, and retain the simplest queue backend that meets current load.

Current state: recent completed `camera_events` showed three-to-five-second Laravel acceptance-to-completion intervals with an empty queue. The worker's former `--sleep=3` polling interval could account for up to three seconds before processing began, while an earlier direct Dahua-to-MAX observation measured 2,569 ms of external work. Development and production now use configurable `QUEUE_SLEEP=1`; this improves single-event pickup latency without Redis or extra workers. Notification time includes seconds and remains explicitly the server's webhook acceptance time because the tested Dahua callback carries no device event timestamp. The next useful observation is a controlled real event after recreating the worker, comparing the NVR detection time, event-history creation time, and MAX arrival time.

## Phase 28 — Early event snapshot capture

Capture the visual evidence before queue waiting and MAX network calls can move the frame away from the actual event.

Current state: implemented and covered. After normal webhook acceptance and duplicate claiming, Laravel creates the journal event, immediately requests one Dahua JPEG, and writes it to the private local disk. Only then is the database job dispatched. The job reads that file instead of requesting a later Camera frame and deletes it through both `finally` and failure-callback paths. Capture or storage failure records a safe terminal failure, removes any partial file, releases the duplicate key, returns `503`, and does not dispatch. The production `app` and `queue` containers already share `app-storage`, so no new service or volume is required. A controlled real event is still required to measure the new webhook response duration and verify that the earlier frame contains the vehicle.

## Rule for changing the roadmap

Before adding a new tool, abstraction, or service, ask:

1. What real problem do we have now?
2. Can Laravel already solve it with something present?
3. Does introducing it improve understanding?
4. Is this the right time, or can it wait?
