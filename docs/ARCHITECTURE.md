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

As of 2026-09-14, the application contains:

- the authentication and settings UI supplied by the Livewire Starter Kit;
- a minimal `cameras` table with `id`, `name`, `is_active`, and timestamps;
- a `Camera` Eloquent model with explicit mass-assignment rules and a boolean cast;
- a Camera factory and focused PostgreSQL feature test;
- a `clients` table and Client model with unique `max_chat_id`;
- a Client factory and focused persistence/constraint tests;
- a conventional `camera_client` pivot table with unique Camera–Client pairs and cascading pivot cleanup;
- reciprocal, typed `BelongsToMany` relationships between Camera and Client;
- feature tests for bidirectional relationship reads, duplicate-pair rejection, and deletion behavior.
- a protected Livewire Cameras administration page and sidebar navigation entry;
- a read-only Cameras table with name ordering, active/inactive status display, and an empty state (currently uncommitted and awaiting focused list-test verification).

There is no webhook, queue job, Redis service, Dahua/MAX HTTP client, or event journal yet.

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
queue backend (likely Redis)
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

## External integrations

### Dahua

Use Laravel HTTP Client when implemented.

Potential responsibilities:

- snapshot request;
- digest authentication;
- timeouts;
- response validation.

Understand the HTTP contract first, using Postman when helpful.

### MAX Messenger

Use Laravel HTTP Client.

Potential responsibilities:

- authentication;
- upload URL creation;
- image upload;
- message send;
- API errors.

Understand the real HTTP sequence before adding abstractions.

Do not put external API calls directly in Livewire components or Eloquent models.

## Data storage

Primary DB: PostgreSQL.

The current Camera schema is deliberately limited to identity, display name, enabled state, and timestamps. Network details, credentials, rules, and integration fields will be added only after their requirements and security implications are understood.

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

Not required at bootstrap.

Expected future uses:

- queue backend;
- duplicate-event protection;
- cache;
- locks where justified.

Introduce it when one of those is a real requirement.

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

Later/optional:

- Redis when needed;
- Mailpit only if needed.

The default Laravel database-backed cache, session, and queue configuration is present. This does not mean queue processing has been introduced into the application; no application jobs or workers are currently part of the design.

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
