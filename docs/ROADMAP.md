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

As of 2026-09-21:

- Phases 0–6 are complete, committed, and pushed.
- Phase 7 is complete and committed: the application has a protected Cameras Livewire page, navigation entry, custom Flux-compatible icon, and access tests.
- Phase 8 is complete, committed, and pushed through `cb1adf0` for the current minimal Camera schema: listing, creation, filtering, pagination, editing, and confirmed deletion are implemented and covered.
- Phase 9 is in progress through `2107f1e` plus an uncommitted Client-creation slice: the form and validation are implemented, success/required/integer tests are written, and duplicate-ID coverage plus verification remain before commit.
- Redis, background jobs, external API clients, and event persistence remain intentionally postponed.

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

Only now choose/configure a queue backend.

## Phase 19 — Redis

Introduce Redis because there is now a concrete use-case.

Learn Redis basics, queue backend, Sail service, and Laravel configuration.

## Phase 20 — Duplicate-event protection

Learn Cache, TTL, atomic operations, and race conditions.

Use Redis if it fits the proven requirement.

## Phase 21 — Camera event journal

Design persistence only after we know which event-processing information is useful.

Learn statuses, logs vs operational history, and indexes.

## Phase 22 — Dashboard and event UI

Learn Livewire filtering/pagination and Flux tables/badges/modals.

## Phase 23 — Email only if needed

If password reset, verification, or email notifications are actually used, introduce Mailpit for local development.

Do not add it merely because Laravel supports mail.

## Phase 24 — Hardening

Authorization, secrets, encrypted fields, rate limiting where justified, queue recovery, indexes, logs, backups.

## Phase 25 — Deployment preparation

Production environment, queue worker lifecycle, HTTPS/reverse proxy basics, persistence, backups, and migrations.

## Rule for changing the roadmap

Before adding a new tool, abstraction, or service, ask:

1. What real problem do we have now?
2. Can Laravel already solve it with something present?
3. Does introducing it improve understanding?
4. Is this the right time, or can it wait?
