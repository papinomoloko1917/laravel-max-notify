# max-notify — Architecture

Status: early implementation; future integration flow remains an architecture hypothesis.

This is direction, not a complete up-front design.

## Learning principle

Do not implement the final architecture all at once.

Use:

simple synchronous behavior
→ understand real limitation
→ introduce next mechanism
→ refactor with evidence

Queues and Redis should appear only after the synchronous webhook and slow external I/O are understood.

## Product responsibility

`max-notify` receives Dahua camera events and notifies configured MAX Messenger recipients with camera snapshots.

It also provides an authenticated administrative UI.

## Currently implemented slice

As of 2026-10-16, the application contains:

- the authentication and settings UI supplied by the Livewire Starter Kit;
- a `cameras` table with identity/display fields plus an encrypted URL-token credential and optional unique HTTP Basic username/hashed password;
- a `Camera` Eloquent model with explicit mass-assignment rules, boolean and hashed casts, and hidden webhook-password serialization;
- a Camera factory and focused PostgreSQL feature test;
- a `clients` table and Client model with unique `max_chat_id`;
- a Client factory and focused persistence/constraint tests;
- a conventional `camera_client` pivot table with unique Camera–Client pairs and cascading pivot cleanup;
- reciprocal, typed `BelongsToMany` relationships between Camera and Client;
- feature tests for bidirectional relationship reads, duplicate-pair rejection, and deletion behavior;
- a protected Livewire Cameras administration page and sidebar navigation entry;
- a Cameras table with name ordering, active/inactive status display, an empty state, database-backed pagination, and Flux UI pagination controls;
- focused HTTP feature tests for access, ordered rendering, statuses, and the empty state;
- a Livewire Camera creation action that validates, persists, resets its name field, and reloads the list;
- a Livewire Camera editing flow with separate edit state, a shared Flux modal, validated updates, and modal closing after success;
- a confirmed Camera deletion flow with separate selection state, a shared translated confirmation modal, destructive styling, state cleanup, and modal closing;
- a reactive database-backed all/active/inactive Camera filter;
- focused HTTP list tests and Livewire component tests for creation, validation, filtering, page navigation, filter-driven page reset, edit-state loading, successful updating, rejected invalid edits, deletion selection, and confirmed deletion;
- a protected Livewire Clients administration page with sidebar navigation, name ordering, database pagination, MAX chat IDs, an empty state, and assigned-Camera totals calculated with `withCount()`;
- focused Client page tests for access, ordering, chat IDs, assigned-Camera counts, and the empty state;
- a Client creation form with required name validation, integer/unique MAX chat ID validation, persistence, field reset, and focused coverage including rejected duplicate IDs;
- a Client editing flow with separate edit state, `findOrFail()` selection, a shared Flux modal, validated persistence, current-record exclusion from the unique MAX chat ID rule, state cleanup, modal closing, and focused coverage for successful updates, rejected missing names, conflicting MAX chat IDs, and unchanged own MAX chat IDs;
- a confirmed Client deletion flow with separate selection state, a shared translated confirmation modal, destructive styling, state cleanup, modal closing, and focused selection/deletion coverage;
- Client Camera assignments edited through a separately paginated table in the existing modal; current IDs are loaded from the many-to-many relation, submitted IDs are validated individually, and `sync()` makes pivot rows match the selected set;
- focused assignment tests cover replacing an existing Camera relation and rejecting a nonexistent Camera ID without changing Client or pivot data.
- a protected Livewire event-history page with newest-first pagination, status filtering, safe details, and an explicit processed-history cleanup action; the destructive action removes only terminal events and preserves pending queue work;
- a protected Livewire Dashboard that reads the current Camera, active-Camera, Client, lifecycle-status, queued-job, and failed-job totals directly from PostgreSQL and shows the five newest CameraEvents with a link to the full journal; queue health is read-only and deliberately exposes neither payloads/exceptions nor retry controls;
- one password-confirmed MAX-bot settings page that validates replacement tokens through `GET /me`, stores the active token encrypted at rest, and renders only non-secret bot identity;
- one nullable-for-migration channel and snapshot connection per Camera; the `(snapshot_base_url, channel)` pair is unique, allowing separate devices to reuse channel numbers;
- per-Camera event-rule administration with prepared event types, a URL-safe custom option, a unique technical code and readable notification title for each rule;
- generated copyable webhook URLs assembled from configured `DAHUA_WEBHOOK_BASE_URL` (falling back to `APP_URL`), Camera ID, its encrypted-at-rest random token, channel, and application-owned rule code; the current browser host is deliberately ignored, while compatible devices may still send separate Basic credentials in `Authorization`;

A minimal webhook endpoint performs per-Camera authentication through either HTTP Basic or a Camera ID plus 64-character bearer token in the URL, validates static IVS query metadata, applies the notification window, and atomically suppresses identical Camera/channel/rule requests for five seconds through the database cache. Camera administration accepts optional paired Basic username/password credentials, hashes the password, never reloads it into Livewire, and preserves the existing hash when the replacement field is empty. Every Camera also receives a random token encrypted through the Eloquent cast; its rules page renders the complete URL and provides explicit rotation that immediately invalidates prior URLs. Every authentication failure returns the same `401 Unauthorized` with a standard Basic challenge. After authentication the endpoint resolves the exact per-Camera rule mapping, snapshots the readable title (or the raw code as a lossless fallback) into one `pending` `CameraEvent`, dispatches `ProcessCameraEvent` with the Camera ID, channel, and event ID, safely logs accepted context, and returns plain text. Rejected, outside-window, and immediate duplicate requests do not create journal rows. The worker reloads current Camera/Client state and records `skipped` outcomes for a missing Camera, inactive Camera, or no recipients. For an active Camera with recipients, it stores the recipient total before external I/O, retrieves one Dahua snapshot, requests one MAX upload URL, uploads the JPEG once, and reuses its media token to send one formatted text-and-image message to every assigned Client. The message uses the event's title snapshot, Camera-name snapshot, and acceptance timestamp formatted in the application timezone. The sent count advances only after each successful message; complete delivery records `completed` and `processed_at`. The queue failure callback records `failed`, a safe exception class (never its message), and `processed_at` without resetting partial counters; a missing journal row is ignored to avoid masking the original failure. MAX requests are bounded to 3 seconds for connection establishment and 10 seconds overall; the job is bounded to 80 seconds before `retry_after=90`, fails on timeout, and explicitly permits one attempt. Whole-job retries/backoff are intentionally absent until partial-delivery duplicates can be addressed; Redis remains deferred.

## Likely mature flow

```text
Dahua camera
    |
    v
Laravel webhook endpoint
    |
    +--> authenticate / validate
    +--> identify camera
    +--> apply event/time rules
    +--> duplicate protection
    |
    v
queue job
    |
    v
queue backend (database first; Redis evaluated later)
    |
    v
process camera event
    |
    +--> Dahua HTTP client -> snapshot
    |
    +--> MAX HTTP client -> upload/send
    |
    v
persist useful event history
```

This is NOT the implementation order.

## Web UI

Use Laravel + Livewire 4 + Flux UI + Tailwind CSS 4.

Likely sections:

- Dashboard;
- Cameras;
- Clients;
- Event history;
- Settings when real settings exist.

Livewire must not own machine-to-machine webhook processing.

## Administrative access

The current installation is a closed, single-role administration system. Fortify public registration and email verification are disabled, and every trusted User provisioned in the database is intentionally an administrator after authentication. Administrative and settings routes therefore use `auth`; the Security settings page additionally retains `password.confirm`.

Email verification is not authorization. `User` does not implement `MustVerifyEmail`, so the former `verified` middleware did not restrict access and has been removed. Do not add `is_admin`, roles, Gates, or Policies until a real non-administrator user type or permission boundary exists.

Fortify two-factor authentication is also intentionally disabled and the current User schema has no two-factor columns. Starter Kit factory, component, provider, and test remnants for that unavailable feature were removed; the authenticated password-change flow remains supported. Reintroduce two-factor authentication only as a complete schema, configuration, UI, and test change.

## Webhook boundary

Dahua events enter through normal Laravel HTTP routing/controller handling.

The mature webhook should be secure, deterministic, and short-running, but the first version may intentionally be synchronous for learning.

The endpoint identifies an active Camera either by its unique Basic username and hashed password or by the Camera ID and constant-time comparison of its decrypted URL token. If an Authorization header is present, Basic validation is authoritative and the request cannot fall back to its URL token. Missing credentials, unknown selectors, wrong secrets, and inactive Cameras deliberately share one `401 Unauthorized` response with `WWW-Authenticate: Basic realm="max-notify"`. Safe warning logs distinguish missing or unsupported credentials from invalid credentials and record only the authorization scheme, never a username, password, token, query string, or complete Authorization header. Only after authentication does the endpoint validate the query contract: exact `event=ivs`, integer channel `>= 1`, and a required bounded rule label; malformed metadata receives plain-text `400` rather than browser redirect behavior. Accepted requests log only Camera identity and validated metadata.

Before the controller, the `dahua-webhook` named limiter applies two independent fixed-window budgets: 60 requests/minute per presented Basic username or non-secret Camera ID selector, and 300 requests/minute per source IP by default. Both values are environment-backed. Basic usernames are hashed before becoming cache keys; URL tokens never enter limiter keys. The IP bucket limits aggregate abuse but is not used to identify a Camera. A rejected flood receives Laravel's `429 Too Many Requests` with `Retry-After`. This security boundary intentionally precedes authentication, while the later five-second Camera/channel/rule cache claim solves business duplicate delivery after authentication and notification-window checks.

Current hardware observations from the DHI-NVR4232-4KS2/L IVS "Send command" action:

- it calls the configured URL with `GET`, no request body, and no `Content-Type` header;
- event and Camera identifiers in the tested query string are application-chosen static URL values rather than an NVR-generated payload; an `event=smd` test value remained unchanged when IVS actually triggered the call;
- the Docker-network source IP is not suitable as Camera identity;
- the static request has no device-generated event identifier with which to distinguish a retry from a separate detection; two IVS calls eight seconds apart were associated with two distinct triggers under a five-second anti-dither setting, and duplicate delivery for one controlled IVS trigger has not been observed.
- with the NVR Authentication option enabled, it sends HTTP Basic credentials preemptively; the application must never log their values, and HTTPS is required before treating those credentials as confidential on an untrusted network.
- the tested `DH-IPC-HDW3449HP-AS-PV-0280B-S3` stored HTTP-action credentials but sent repeated requests without an Authorization header under the observed firmware/settings; its compatible path is the generated bearer-token URL with device Authentication disabled.

These findings describe the tested local setup, not yet a permanent public webhook contract.

## External integrations

### Dahua

The Dahua client uses Laravel HTTP Client and accepts a base URL, Digest username/password, and channel for each snapshot request. Connection/overall limits remain environment-backed at 3/10 seconds. It converts `4xx/5xx` responses to Laravel HTTP exceptions, rejects successful non-JPEG responses, and returns validated JPEG bodies as raw string bytes. Real-device Tinker smoke checks confirmed Laravel/Guzzle Digest negotiation and JPEG retrieval both before and after moving the connection to the Camera record.

Potential responsibilities:

- snapshot request;
- digest authentication;
- timeouts;
- response validation.

Understand the HTTP contract first, using Postman when helpful.

The snapshot contract observed against the current NVR is a Digest-authenticated `GET /cgi-bin/snapshot.cgi?channel=<channel>`. A valid channel returns `200 OK` and `image/jpeg`; a wrong password returns `401 Unauthorized` with a plain-text error; a nonexistent channel returns `400 Bad Request` with a different plain-text error. These are observed device behaviors rather than assumptions from vendor documentation.

Each Camera owns its snapshot device origin, Digest username, encrypted Digest password, and channel. This supports direct IP cameras and multiple recorders without selecting one installation-wide host. Cameras on different origins may reuse the same channel, while duplicate channels on the same origin are rejected. Incoming webhook credentials remain separate: their password is intentionally hashed and cannot be reused for outgoing Digest authentication.

### MAX Messenger

Use Laravel HTTP Client.

Potential responsibilities:

- authentication;
- upload URL creation;
- image upload;
- message send;
- API errors.

Understand the real HTTP sequence before adding abstractions.

The observed image workflow uses the raw bot token in the `Authorization` header: request an upload URL with `POST /uploads?type=image`, upload one JPEG as multipart field `data`, read the resulting `photos.0.token`, then send it as an image attachment through `POST /messages?chat_id=<id>`. `GET /updates` is suitable for discovering a test dialog's `chat_id`, but not as the production event-delivery design.

`MaxClient` implements bot inspection, upload-URL creation, multipart JPEG upload, and image-message sending. `GET /me` validates a candidate token before it can replace the active bot. Runtime requests resolve the encrypted singleton database token first and use `services.max.token` only as an initial/fallback value. The client reads the base URL and 3/10-second connection/overall limits from `services.max`, uses the raw `Authorization` header, rejects HTTP errors, and validates the response fields it consumes. Keeping these methods separate makes each HTTP boundary independently observable and testable while the queued job composes the workflow.

The current MAX endpoints present a certificate chain issued by `Russian Trusted Sub CA`. The published Sail 8.5 image installs the official Russian Trusted Root/Sub certificates into its system CA store so Laravel HTTP Client can keep normal TLS verification enabled. Disabling verification is not an accepted workaround. This trust-store customization belongs to reproducible container infrastructure and must be rebuilt on each machine.

Do not put external API calls directly in Livewire components or Eloquent models.

## Data storage

Primary DB: PostgreSQL.

The Camera schema contains identity, display name, enabled state, notification window, timestamps, webhook authentication, and the outbound snapshot connection. `snapshot_base_url` stores only an HTTP(S) device origin, `snapshot_username` stores the Digest login, and `snapshot_password` uses Laravel's encrypted cast and is hidden from serialization. New/edited Cameras require the connection, never hydrate the stored password into Livewire, and preserve it when the replacement field is empty. The nullable migration allows existing rows to be backfilled safely. A composite unique constraint on `(snapshot_base_url, channel)` prevents duplicate logical channels on one device without preventing channel `1` on multiple IP cameras. `webhook_username` remains nullable/unique; `webhook_password` remains one-way hashed and hidden. Outgoing Digest and incoming webhook credentials are deliberately separate because only the former must be recoverable by the application.

The Camera schema includes a nullable PostgreSQL `time` pair, `notify_from`/`notify_until`, for one daily notification window. Both null values mean no time restriction. A start later than the end denotes a window crossing midnight (`21:00–06:00`); midnight is stored as `00:00`, not as a separate `24:00` endpoint. Multiple disjoint daily windows are not currently required. The installation timezone is read from `APP_TIMEZONE` and currently set to `Europe/Moscow`.

`App\Services\NotificationWindow` is an isolated deterministic rule: callers supply the current `CarbonInterface`, starts are inclusive, ends are exclusive, `null/null` disables filtering, and partial or equal boundaries fail closed. The webhook controller applies it after Camera authentication and metadata validation. Requests outside the configured window receive plain-text `200 Ignored` before accepted-event logging so the NVR does not interpret the deliberate skip as a delivery failure. The Camera edit form loads and persists both time fields using `HH:MM`, maps two empty inputs to unrestricted `null/null`, and rejects partial or equal-boundary configurations before persistence.

Initial conceptual entities:

- User;
- Camera;
- Client;
- CameraEvent, now used as the minimal operational event journal.

Expected Camera ↔ Client relation: many-to-many.

Current relationship decision:

- pivot table: `camera_client`;
- foreign keys: `camera_id` and `client_id`;
- duplicate pairs are forbidden by a composite unique constraint;
- deleting a Camera or Client cascades only to its pivot rows;
- pivot timestamps record assignment creation and updates and are populated by Eloquent through `withTimestamps()` on both relationships.

`CameraRule` belongs to one Camera and maps one exact, case-sensitive webhook `rule` code to a readable notification title. `config/camera_events.php` contains the small application-owned catalog of common events; selecting one creates both fields without exposing the technical code. Custom identifiers are restricted to ASCII letters, digits, `_`, and `-`. The composite unique constraint on Camera and code permits multiple different rules per Camera without ambiguous mappings; deleting a Camera cascades its configuration rules. The generated URL automates application metadata but must still be copied into the corresponding NVR `Send command` action because no supported remote configuration contract has been established.

`CameraEvent` stores the Camera reference and name snapshot, channel, raw rule, resolved rule-title snapshot, lifecycle status, aggregate recipient totals, an optional failure type, and processing time. Its nullable Camera foreign key uses `nullOnDelete()`: deleting configuration must not erase operational history. Accepted webhooks create `pending` rows, and the queue job terminates them as `skipped`, `completed`, or `failed`. Terminal history is retained for 30 days by default and pruned daily; the period is environment-backed. Pending rows are excluded from both automatic pruning and the administrator's confirmed cleanup action because queued work may still reference them.

PostgreSQL indexes follow current query and foreign-key paths rather than every column. `camera_events(status, id)` supports Dashboard status counts and filtered newest-first history; `camera_events(camera_id)` supports relationship reads and avoids scanning an unbounded journal when a Camera is deleted; `camera_client(client_id)` supports Client-to-Camera reads and Client cascade deletion, while the existing unique `(camera_id, client_id)` index already covers the opposite direction. Primary, unique, queue, and cache indexes remain unchanged. Separate indexes for boolean `is_active`, administrative name ordering, or other speculative combinations are deferred until scale or query plans justify them.

## Redis

Not currently required. Phase 19 evaluated it after the database queue lifecycle was understood and deliberately retained PostgreSQL for queue and cache at the current low volume.

The existing database cache supports atomic `Cache::add()` through a primary-key insert and provides database-backed locks using the already migrated `cache` and `cache_locks` tables. The first five-second duplicate-event guard now uses that store successfully without adding another service.

Expected future uses:

- queue backend;
- duplicate-event protection;
- cache;
- locks where justified.

Re-evaluate it when measured queue latency/throughput, cache/lock contention, or another concrete operational requirement justifies the extra service.

## Postman

Development/learning tool for exploring:

- Dahua webhook;
- Dahua HTTP API;
- MAX Messenger API.

It is not runtime infrastructure and does not replace automated tests.

## Mailpit

Optional development service only if email functionality is actually used.

## Adminer

Optional local PostgreSQL UI. Helpful for inspecting tables/rows/indexes, but not an application dependency.

Never expose it publicly in production.

## Secrets

Use `.env`/Laravel config for environment-specific secrets that require operator control. The single active MAX bot is a real administrator-managed application setting: its token is encrypted by Laravel in PostgreSQL, hidden from model serialization, never rendered after saving, and depends on the installation `APP_KEY` for recovery.

Never commit MAX token, webhook secrets, camera passwords, or production credentials.

Per-Camera snapshot passwords, URL webhook tokens, and the singleton MAX token use Laravel encrypted casts and depend on the installation `APP_KEY`; optional Basic webhook passwords remain one-way hashed.

The accepted Dahua webhook log has an explicit context whitelist: Camera ID/name plus validated event, channel, and rule. Basic credentials, URL tokens, query strings, and authorization headers are excluded. `ProcessCameraEvent` serializes only Camera ID, channel, and CameraEvent ID; webhook/Dahua credentials and the MAX token are resolved at handling time and therefore are absent from the database queue payload. Regression tests protect both boundaries.

Application-owned `camera_events.failure_type` stores only a controlled skip code, an exception class, or `unknown`, never an exception message. Laravel's framework-managed `failed_jobs` table intentionally retains full diagnostic exception data for trusted operators, but the administration UI shows only its row count and never renders its payload or exception text.

## Development environment

Current Sail services:

- Laravel/PHP;
- PostgreSQL;
- Adminer as optional local inspection tooling.

The committed Sail 8.5 runtime installs the official Russian Trusted Root/Sub CA chain required by the current MAX endpoints. This keeps normal TLS verification enabled and makes the trust-store change reproducible on each development machine after rebuilding the application image.

Later/optional:

- Redis when needed;
- Mailpit only if needed.

The default Laravel database-backed cache, session, and queue configuration is present, including the `jobs` and `failed_jobs` tables. Manual development runs observed both successful removal after `queue:work --once` and storage in `failed_jobs` after one controlled HTTP-fake exception; the failure experiment was cleaned up completely. The Dashboard counts rows through the configured database queue connections and table names, making backlog and terminal failures visible without exposing serialized payloads or exception text. The job handler has database-state guards and completes snapshot retrieval, upload-URL creation, one JPEG upload, and one message send per assigned Client. Its explicit 80-second timeout is below `retry_after=90`, timeout termination marks it failed, and `$tries=1` prevents worker CLI settings from enabling unsafe whole-job retries. Development Compose runs one persistent database queue worker and one Laravel scheduler process with automatic restart. The scheduler executes the named `camera-events:prune` command daily at 03:00; command and UI behavior share the same terminal-status boundary. The database backend remains the working baseline while Redis is evaluated against real queue and duplicate-protection needs.

Failed-job recovery is intentionally operator-only. Diagnostics are retained for 30 days, investigated through protected CLI/database access, and removed individually with `queue:forget` after resolution; old records may be pruned with `queue:prune-failed --hours=720`. `queue:retry` is prohibited for `ProcessCameraEvent` because whole-job replay can duplicate messages already sent before a partial failure. Production now supervises a persistent worker, while failed-job pruning remains a reviewed operator action. Backup automation and a restore rehearsal still depend on the selected off-device destination.

Demonstration User and Camera seeders are restricted to the `local` environment. The predictable local user is created or updated idempotently; its populated `email_verified_at` value is conventional seed data and is not an authorization requirement.

A new machine should be recoverable from Git, lock files, `.env.example`, migrations, and documented setup.

## Production target

The first deployment target is a dedicated on-premises MiniPC MA01 (Intel N150, 16 GB RAM, 512 GB storage) running Ubuntu Server 26.04.1 LTS on the same LAN as the Dahua NVR. Production uses Docker Engine plus the Compose plugin and the dedicated `compose.production.yaml`; the Sail development `compose.yaml` is not a production artifact.

Production will use a separate immutable application image and topology: reverse proxy, PHP-FPM application, one database queue worker, one Laravel scheduler, and an internal-only PostgreSQL service with persistent storage. Only the reverse proxy publishes host ports; Adminer, Vite, Xdebug, source bind mounts, the development PHP server, and a public database port are excluded. Redis remains unnecessary at the observed load.

The implemented topology contains Caddy, an immutable PHP-FPM application image, one database queue worker, one scheduler, and PostgreSQL on an internal Docker network. Only Caddy publishes ports. The administration UI uses Caddy's private-CA HTTPS listener on `443`. Because the tested Dahua device cannot be assumed to trust that CA, port `80` serves only `/webhooks/dahua`, disables access logging, and returns `404` for administration paths. HTTPS access logging skips the same webhook path, and PHP-FPM access logging is disabled so its default request-URI format cannot leak URL tokens. `DAHUA_WEBHOOK_BASE_URL` separates the generated device URL from the HTTPS `APP_URL`.

The production image includes compiled frontend assets and the Russian Trusted Root/Sub CA chain required by the observed MAX endpoint. It excludes development dependencies, Sail, Vite runtime, Xdebug, Adminer, source bind mounts, and published PostgreSQL/PHP-FPM ports. CameraEvent pruning runs through the single scheduler; the queue worker preserves the existing one-attempt and 80-second timeout boundary.

Exact source-IP firewall restrictions still wait for the static server/NVR addresses and administrator access scope. PostgreSQL backups must leave the MiniPC because a volume on its single SSD is not a backup. The production `APP_KEY`, environment, Caddy CA state, Git revision, and database dump form the recovery boundary. See `docs/DEPLOYMENT.md` for the complete Russian procedure and remaining site-specific inputs.

## Testing strategy

Prefer:

- Feature tests for HTTP/webhook behavior;
- Feature tests for important DB/Livewire behavior;
- Unit tests for isolated business rules when useful;
- `Http::fake()` for Dahua/MAX.

Postman is exploratory testing, not regression testing.

## Architectural constraints

Avoid until a real problem requires them:

- repository layer over Eloquent;
- custom service container;
- CQRS;
- event sourcing;
- broad DTO layers;
- microservices;
- unnecessary interfaces;
- a separate SPA framework.

## Legacy project

Reuse business knowledge, event semantics, integration details, and proven edge cases from the old plain-PHP project.

Do not automatically copy homemade DI/DB/routing/auth infrastructure, file-based duplicate protection, or its synchronous architecture.
