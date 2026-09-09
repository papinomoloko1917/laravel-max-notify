# max-notify — Architecture

Status: initial architecture hypothesis.

This document describes decisions we currently believe are appropriate. It is deliberately not a complete up-front design.

When implementation proves a decision wrong, update this document.

---

## 1. Product responsibility

`max-notify` receives events from Dahua cameras and notifies configured MAX Messenger recipients with camera snapshots.

It also provides an authenticated administrative interface for configuration and event history.

---

## 2. High-level flow

Target direction:

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
dispatch queue job
    |
    v
Redis queue
    |
    v
Process camera event
    |
    +--> Dahua client -> snapshot
    |
    +--> MAX client -> upload/send
    |
    v
persist useful event status/history
```

The exact point at which event history is written will be decided while implementing the workflow.

---

## 3. Web UI

Technology:

- Laravel;
- Livewire 4;
- Flux UI;
- Tailwind CSS 4.

The administrative interface is intended for human users.

Likely sections:

- Dashboard;
- Cameras;
- Clients;
- Event history;
- Settings.

Livewire should not own machine-to-machine webhook processing.

---

## 4. HTTP/webhook boundary

Dahua events enter through a standard Laravel HTTP route/controller boundary.

The webhook should be optimized for:

- correct validation;
- security;
- deterministic responses;
- short synchronous execution;
- safe handoff to asynchronous processing.

Do not perform unnecessarily slow external API work before responding to the camera.

We will first understand/test the synchronous contract, then introduce queues.

---

## 5. Background processing

Redis-backed Laravel Queue is the intended asynchronous mechanism.

Expected asynchronous responsibilities:

- snapshot retrieval;
- MAX upload;
- MAX message sending;
- retryable external API work.

Queue behavior must eventually define:

- retry policy;
- timeouts;
- failure behavior;
- idempotency;
- observability.

Do not choose values until real integration behavior is understood.

---

## 6. External integrations

### Dahua

Implement behind a small service/client boundary using Laravel HTTP Client.

Responsibilities may include:

- snapshot request;
- digest authentication;
- timeout handling;
- response validation.

It must be testable with fake HTTP responses.

### MAX Messenger

Implement behind a small service/client boundary using Laravel HTTP Client.

Responsibilities may include:

- authentication;
- upload endpoint creation;
- binary/image upload;
- message send;
- API error handling.

It must be testable with fake HTTP responses.

Avoid putting API calls directly inside Livewire components or Eloquent models.

---

## 7. Data storage

Primary database: PostgreSQL.

Initial conceptual entities:

### User

Authenticated administrator.

### Camera

Represents a Dahua camera and the configuration required to process its events/snapshots.

Exact schema is intentionally postponed until its learning phase.

### Client

Represents a notification recipient/customer.

The exact MAX identifier fields will be confirmed from the integration requirements.

### Camera ↔ Client

Expected many-to-many relationship.

A camera may notify multiple clients.
A client may receive notifications from multiple cameras.

### CameraEvent

Expected operational history of received/processed events.

This is not intended to store every possible log line.

It should store enough structured information to answer questions such as:

- what camera generated the event?
- when?
- was it skipped?
- was notification successful?
- how many recipients were targeted?
- what useful failure reason occurred?

Exact schema will be designed when the processing flow exists.

---

## 8. Redis

Intended uses:

- Laravel Cache;
- duplicate-event protection;
- Laravel Queue.

Potential future use:

- locks;
- rate limiting.

Use atomic cache primitives for duplicate protection rather than a local JSON/file lock mechanism.

---

## 9. Configuration and secrets

Use `.env`/Laravel config for environment-specific application secrets.

Examples likely to belong outside source control:

- MAX token;
- webhook shared secrets;
- infrastructure credentials.

Camera credentials may be database data if each camera has different credentials.

If so, credentials must not be stored in plain text without considering Laravel encryption-at-rest support.

Never place production secrets into documentation or Git history.

---

## 10. Development environment

Laravel Sail is the intended local development environment.

Expected services:

- Laravel/PHP;
- PostgreSQL;
- Redis;
- Adminer;
- Mailpit if useful.

Adminer is local tooling only.

Local database/Redis Docker volumes are not the source of truth.

A new machine should be recoverable from:

- Git repository;
- dependency lock files;
- `.env.example`;
- migrations;
- seeders/factories where appropriate.

---

## 11. Testing strategy

Testing will grow with the application.

Prefer:

- Feature tests for HTTP/webhook behavior;
- Feature tests for important database/Livewire behavior;
- Unit tests for isolated business rules where isolation is valuable;
- `Http::fake()` for Dahua and MAX;
- queue/cache fakes where they improve test focus.

Do not make tests artificially isolated when a normal Laravel feature test is clearer.

---

## 12. Architectural constraints

For now, avoid:

- custom repository layer over Eloquent;
- custom service container;
- CQRS;
- event sourcing;
- broad DTO layers;
- microservices;
- unnecessary interfaces for every class;
- a separate SPA framework.

These can be revisited only if a concrete problem appears.

---

## 13. Legacy project relationship

The previous plain-PHP `max-notify` implementation is a behavioral reference.

We may reuse:

- business knowledge;
- event semantics;
- integration details;
- proven edge cases.

We should not automatically copy:

- homemade DI;
- homemade database infrastructure;
- manual routing;
- manual session/auth infrastructure;
- file-based duplicate protection;
- synchronous architecture where queues are more appropriate.

Laravel should be allowed to solve framework-level concerns in the Laravel way.
