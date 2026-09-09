# max-notify — Learning & Development Roadmap

This roadmap is intentionally incremental. It is not a promise that every item will be implemented exactly as written.

The rule is: learn one concept, implement a small piece, review it, then continue.

## Project outcome

A Laravel application that:

- receives Dahua camera events;
- processes them safely and asynchronously;
- retrieves camera snapshots;
- sends notifications through the MAX Messenger API;
- manages cameras and clients through an authenticated web UI;
- stores an event history;
- runs locally through Laravel Sail with PostgreSQL and Redis.

---

## Phase 0 — Repository and development environment

### Learning goals

- understand the structure of a Laravel project;
- understand Composer/npm dependencies;
- understand Laravel Sail and Docker services;
- understand `.env` vs `.env.example`;
- practice basic Git workflow.

### Expected result

A fresh project can be cloned on another machine and brought up reliably.

Likely services:

- Laravel application;
- PostgreSQL;
- Redis;
- Adminer;
- Mailpit if useful.

### Exit criteria

- application opens locally;
- PostgreSQL connection works;
- Redis connection works;
- migrations can run;
- `.env` is not tracked;
- `.env.example` contains no secrets;
- setup steps are documented.

---

## Phase 1 — Understand and model the domain

### Learning goals

- translate real-world concepts into database entities;
- distinguish entity data from configuration;
- understand relationships and constraints.

Initial domain candidates:

- User;
- Camera;
- Client;
- CameraEvent.

Questions to resolve before implementation:

- Which camera properties are required?
- How are clients assigned to cameras?
- Which values belong to application configuration rather than the database?
- What does an event need to record for debugging?

### Exit criteria

We can explain the data model in plain language before building it.

---

## Phase 2 — Cameras: migrations and Eloquent

### Learning goals

- migrations;
- PostgreSQL column types;
- indexes;
- Eloquent models;
- casts;
- factories;
- basic feature tests.

Implement Camera incrementally rather than all at once.

Potential fields will be decided during the phase, not copied blindly from the previous implementation.

### Exit criteria

Camera data can be stored, retrieved, validated, and tested.

---

## Phase 3 — Clients and camera/client relationship

### Learning goals

- foreign keys;
- many-to-many relationships;
- pivot tables;
- Eloquent relationship APIs;
- database constraints.

Expected relation:

Camera ↔ Client = many-to-many.

### Exit criteria

A client can be assigned to one or more cameras and the relationship is covered by tests.

---

## Phase 4 — Authentication and administrative shell

### Learning goals

- official Livewire Starter Kit;
- authentication;
- layouts;
- Livewire full-page components;
- Flux UI fundamentals;
- authorization boundaries.

### Exit criteria

An authenticated user can access the administration UI and unauthenticated users cannot.

---

## Phase 5 — Camera management UI

### Learning goals

- Livewire component state;
- validation;
- forms;
- route model binding where appropriate;
- Flux UI forms/modals;
- secure handling of camera credentials.

### Exit criteria

The administrator can create, view, update, enable/disable, and remove cameras safely.

---

## Phase 6 — Client management UI

### Learning goals

- reusable Livewire patterns;
- relationship editing;
- validation;
- form UX.

### Exit criteria

The administrator can manage clients and assign them to cameras.

---

## Phase 7 — Dahua webhook: synchronous foundation

### Learning goals

- machine-to-machine HTTP endpoints;
- request lifecycle;
- controller responsibility;
- validation;
- authentication/secrets;
- response codes;
- logging;
- feature testing webhooks.

Important rule:

Do not start with queues until the synchronous behavior and boundary are understood.

### Exit criteria

A fake Dahua event can be accepted/rejected correctly by automated tests without calling a real camera.

---

## Phase 8 — Event filtering and time windows

### Learning goals

- domain/business rules;
- small testable services or Actions;
- time handling;
- edge cases such as overnight windows.

### Exit criteria

Relevant/non-relevant events and allowed/blocked time windows are deterministic and tested.

---

## Phase 9 — Duplicate-event protection with Redis

### Learning goals

- Redis basics;
- Laravel Cache;
- atomic operations;
- TTL;
- concurrency reasoning.

### Exit criteria

Two equivalent events inside the configured TTL do not both enter expensive processing.

---

## Phase 10 — Queues and background processing

### Learning goals

- Laravel Jobs;
- queue worker;
- Redis queue backend;
- serialization;
- retries;
- timeout/backoff;
- failed jobs;
- idempotency.

### Exit criteria

The webhook can respond quickly while slow event processing happens through a queue worker.

---

## Phase 11 — Dahua HTTP integration

### Learning goals

- Laravel HTTP Client;
- digest authentication;
- timeouts;
- retry decisions;
- exceptions;
- `Http::fake()`.

### Exit criteria

Snapshot retrieval works through an isolated service and is testable without a physical camera.

---

## Phase 12 — MAX Messenger integration

### Learning goals

- multi-step external API workflows;
- authentication headers;
- file uploads;
- API failures;
- fake HTTP responses;
- service boundaries.

### Exit criteria

A snapshot can be prepared and sent to configured recipients, with automated tests covering success and important failure cases.

---

## Phase 13 — Camera event journal

### Learning goals

- event persistence;
- status transitions;
- error visibility;
- useful logging vs database history;
- indexes for filtering.

Possible statuses will be decided based on actual workflow.

### Exit criteria

An administrator can understand what happened to a recent camera event without reading application logs.

---

## Phase 14 — Dashboard and event UI

### Learning goals

- Livewire pagination/filtering;
- Flux tables/badges/modals;
- query performance;
- UX for operational data.

### Exit criteria

The admin UI shows recent events and useful operational state without becoming a monitoring system that is too complex for the project.

---

## Phase 15 — Hardening

### Learning goals

- authorization;
- rate limiting where justified;
- secrets;
- encrypted database fields;
- queue failure recovery;
- structured logging;
- database indexes;
- backup thinking;
- dependency/security review.

### Exit criteria

The project is reasonably safe and observable for its intended use.

---

## Phase 16 — Deployment preparation

### Learning goals

- production environment differences;
- environment variables;
- queue worker lifecycle;
- scheduler if needed;
- cache/config optimization;
- HTTPS/reverse proxy basics;
- PostgreSQL/Redis persistence;
- backups;
- migrations in production.

### Exit criteria

There is a documented deployment plan even if deployment itself uses a separate infrastructure project.

---

## Rule for changing the roadmap

The roadmap may be changed when implementation teaches us something new.

Before adding a large new abstraction or feature, ask:

1. What real problem does it solve now?
2. Can Laravel already solve it with a standard feature?
3. Does adding it help learning, or only add architecture?
4. Can it wait until the problem actually appears?
