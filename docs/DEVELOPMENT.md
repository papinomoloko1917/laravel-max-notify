# max-notify — Development Handoff

This file is the short source of truth for moving between Codex sessions and development machines.

Keep it concise.

Update it at the end of a meaningful work session or when the current learning task changes.

---

## Current phase

Phase 0 — Repository and development environment.

## Current state

The project is at the planning/bootstrap stage.

Target stack:

- Laravel 13;
- PHP 8.4+;
- official Livewire Starter Kit;
- Livewire 4;
- Flux UI;
- Tailwind CSS 4;
- PostgreSQL;
- Redis;
- Laravel Sail;
- Adminer for development;
- Pest;
- GitHub.

The old PHP implementation is a reference for product behavior, not an architecture that must be copied.

## Current learning task

Not assigned yet.

Codex should inspect the actual repository and assign the first small task according to `AGENTS.md`.

## Last completed task

None recorded yet.

## What I learned

Nothing recorded yet.

Use short bullets here after completing a learning task, for example:

- understood what a migration is responsible for;
- learned the difference between `unique()` and a normal index;
- learned how Sail service names are used as database hostnames.

Delete stale bullets periodically. This is not a permanent study notebook.

## Decisions made

Current intended choices:

- use PostgreSQL instead of MySQL;
- use Redis for cache/queues when those phases are reached;
- use the official Livewire Starter Kit;
- use Flux UI instead of Bootstrap/daisyUI;
- use Laravel Sail for development;
- use Adminer only as local development tooling;
- keep Dahua webhook handling outside Livewire;
- use queues for slow camera/MAX work after the synchronous workflow is understood;
- do not port the old application's homemade infrastructure into Laravel.

## Open questions

Resolve only when necessary:

- exact first version of the Camera schema;
- exact Dahua webhook authentication contract;
- exact event/rule fields required from Dahua;
- exact MAX API client design;
- whether global runtime settings belong in config/env or database;
- deployment target.

## Next likely steps

1. Bootstrap/inspect the Laravel project.
2. Verify Sail + PostgreSQL + Redis development environment.
3. Confirm the basic domain model.
4. Start Camera migration/model in small learning steps.

This list is directional only. Codex must give one task at a time.

## Last session handoff

Date: not started.

Machine: not important; repository state is authoritative.

Summary:
- Initial learning documentation prepared.
- No application feature should be considered implemented merely because it appears in the roadmap.

Next action:
- Start Codex in the repository root.
- Ask it to read `AGENTS.md` and the files in `docs/`.
- Let it inspect the repository before assigning the first task.
