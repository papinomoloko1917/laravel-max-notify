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

As of 2026-10-12, the application contains:

- the authentication and settings UI supplied by the Livewire Starter Kit;
- a `cameras` table with identity/display fields plus nullable unique HTTP Basic username and nullable hashed webhook password;
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

A minimal webhook endpoint performs per-Camera HTTP Basic authentication, validates static IVS query metadata, applies the notification window, atomically suppresses identical Camera/channel/rule requests for five seconds through the database cache, dispatches `ProcessCameraEvent`, safely logs accepted context, and returns plain text. The duplicate claim occurs only after the request is otherwise eligible, so rejected and outside-window requests do not consume it. The job carries only the Camera ID and channel, receives integration clients from Laravel's container, and reloads the active Camera with current recipients when a worker handles it. Missing, inactive, and recipient-less Cameras finish without outgoing HTTP. For an active Camera with recipients, the handler retrieves one Dahua snapshot, requests one MAX upload URL, uploads the JPEG once, and reuses its media token to send one image message to every assigned Client. MAX requests are bounded to 3 seconds for connection establishment and 10 seconds overall; the job is bounded to 80 seconds before `retry_after=90`, fails on timeout, and explicitly permits one attempt. Successful and controlled failed worker lifecycles have both been observed. Whole-job retries/backoff are intentionally absent until partial-delivery duplicates can be addressed; Redis and an event journal remain incomplete.

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

## Webhook boundary

Dahua events enter through normal Laravel HTTP routing/controller handling.

The mature webhook should be secure, deterministic, and short-running, but the first version may intentionally be synchronous for learning.

The current minimal endpoint identifies an active Camera by its unique Basic username and verifies the supplied password against the Eloquent-hashed value. Missing credentials, unknown usernames, wrong passwords, and inactive Cameras deliberately share one `401 Unauthorized` response so the endpoint does not reveal which credential check failed. Only after authentication does it validate the query contract: exact `event=ivs`, integer channel `>= 1`, and a required bounded rule label; malformed metadata receives plain-text `400` rather than browser redirect behavior. Accepted requests log only Camera identity and validated metadata. This complete boundary was verified through Postman and the real NVR using channel 7 and the `perimeter` rule.

Current hardware observations from the DHI-NVR4232-4KS2/L IVS "Send command" action:

- it calls the configured URL with `GET`, no request body, and no `Content-Type` header;
- event and Camera identifiers in the tested query string are application-chosen static URL values rather than an NVR-generated payload; an `event=smd` test value remained unchanged when IVS actually triggered the call;
- the Docker-network source IP is not suitable as Camera identity;
- the static request has no device-generated event identifier with which to distinguish a retry from a separate detection; two IVS calls eight seconds apart were associated with two distinct triggers under a five-second anti-dither setting, and duplicate delivery for one controlled IVS trigger has not been observed.
- with the NVR Authentication option enabled, it sends HTTP Basic credentials preemptively; the application must never log their values, and HTTPS is required before treating those credentials as confidential on an untrusted network.

These findings describe the tested local setup, not yet a permanent public webhook contract.

## External integrations

### Dahua

The first minimal client uses Laravel HTTP Client and environment-backed configuration for the single current NVR. It accepts a channel per snapshot request, uses a 3-second connection limit and 10-second overall limit, converts `4xx/5xx` responses to Laravel HTTP exceptions, rejects successful non-JPEG responses, and returns validated JPEG bodies as raw string bytes. A real-device Tinker smoke check confirmed Laravel/Guzzle Digest negotiation and JPEG retrieval. The client remains disconnected from the webhook while the next external contract is explored.

Potential responsibilities:

- snapshot request;
- digest authentication;
- timeouts;
- response validation.

Understand the HTTP contract first, using Postman when helpful.

The snapshot contract observed against the current NVR is a Digest-authenticated `GET /cgi-bin/snapshot.cgi?channel=<channel>`. A valid channel returns `200 OK` and `image/jpeg`; a wrong password returns `401 Unauthorized` with a plain-text error; a nonexistent channel returns `400 Bad Request` with a different plain-text error. These are observed device behaviors rather than assumptions from vendor documentation.

The current installation has one known NVR account shared for snapshot access. The first client may therefore use environment-backed installation configuration for its base URL and credentials while receiving the channel per request. This deliberately supports one NVR; database-backed per-recorder configuration should be introduced only if multiple recorders become a real requirement. Incoming webhook credentials are separate: their password is intentionally hashed and cannot be reused for outgoing Digest authentication.

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

The three `MaxClient` slices implement upload-URL creation, multipart JPEG upload, and image-message sending. They read the base URL/token and 3/10-second connection/overall limits from `services.max`, use the raw `Authorization` header, reject HTTP errors, and validate the response `url`, `photos.0.token`, and `message.body.mid`. Keeping these methods separate makes each HTTP boundary independently observable and testable while the queued job composes the workflow.

The current MAX endpoints present a certificate chain issued by `Russian Trusted Sub CA`. The published Sail 8.5 image installs the official Russian Trusted Root/Sub certificates into its system CA store so Laravel HTTP Client can keep normal TLS verification enabled. Disabling verification is not an accepted workaround. This trust-store customization belongs to reproducible container infrastructure and must be rebuilt on each machine.

Do not put external API calls directly in Livewire components or Eloquent models.

## Data storage

Primary DB: PostgreSQL.

The current Camera schema contains identity, display name, enabled state, timestamps, and the two credentials justified by the observed NVR HTTP Basic contract. `webhook_username` is nullable and unique and identifies the Camera; `webhook_password` is nullable, automatically hashed by Eloquent, and hidden from serialization. Network details and further integration fields remain postponed until their requirements are understood.

The Camera schema includes a nullable PostgreSQL `time` pair, `notify_from`/`notify_until`, for one daily notification window. Both null values mean no time restriction. A start later than the end denotes a window crossing midnight (`21:00–06:00`); midnight is stored as `00:00`, not as a separate `24:00` endpoint. Multiple disjoint daily windows are not currently required. The installation timezone is read from `APP_TIMEZONE` and currently set to `Europe/Moscow`.

`App\Services\NotificationWindow` is an isolated deterministic rule: callers supply the current `CarbonInterface`, starts are inclusive, ends are exclusive, `null/null` disables filtering, and partial or equal boundaries fail closed. The webhook controller applies it after Camera authentication and metadata validation. Requests outside the configured window receive plain-text `200 Ignored` before accepted-event logging so the NVR does not interpret the deliberate skip as a delivery failure. The Camera edit form loads and persists both time fields using `HH:MM`, maps two empty inputs to unrestricted `null/null`, and rejects partial or equal-boundary configurations before persistence.

Initial conceptual entities:

- User;
- Camera;
- Client;
- CameraEvent later, if useful.

Expected Camera ↔ Client relation: many-to-many.

Current relationship decision:

- pivot table: `camera_client`;
- foreign keys: `camera_id` and `client_id`;
- duplicate pairs are forbidden by a composite unique constraint;
- deleting a Camera or Client cascades only to its pivot rows;
- pivot timestamps record assignment creation and updates and are populated by Eloquent through `withTimestamps()` on both relationships.

Do not create `CameraEvent` before we know which operational data is worth storing.

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

Use `.env`/Laravel config for environment-specific secrets.

Never commit MAX token, webhook secrets, camera passwords, or production credentials.

If per-camera credentials live in PostgreSQL, use an encrypted-at-rest approach appropriate for Laravel.

## Development environment

Current Sail services:

- Laravel/PHP;
- PostgreSQL;
- Adminer as optional local inspection tooling.

The committed Sail 8.5 runtime installs the official Russian Trusted Root/Sub CA chain required by the current MAX endpoints. This keeps normal TLS verification enabled and makes the trust-store change reproducible on each development machine after rebuilding the application image.

Later/optional:

- Redis when needed;
- Mailpit only if needed.

The default Laravel database-backed cache, session, and queue configuration is present, including the `jobs` and `failed_jobs` tables. Manual development runs observed both successful removal after `queue:work --once` and storage in `failed_jobs` after one controlled HTTP-fake exception; the failure experiment was cleaned up completely. The job handler has database-state guards and completes snapshot retrieval, upload-URL creation, one JPEG upload, and one message send per assigned Client. Its explicit 80-second timeout is below `retry_after=90`, timeout termination marks it failed, and `$tries=1` prevents worker CLI settings from enabling unsafe whole-job retries. No persistent worker process is configured. The database backend remains the working baseline while Redis is evaluated against real queue and duplicate-protection needs.

Demonstration User and Camera seeders are restricted to the `local` environment. The predictable local user is created or updated idempotently and marked email-verified so it can access verified administration routes.

A new machine should be recoverable from Git, lock files, `.env.example`, migrations, and documented setup.

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
