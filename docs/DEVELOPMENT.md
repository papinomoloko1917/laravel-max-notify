# max-notify — Development Handoff

Short source of truth for moving between Codex sessions and machines.

Keep this concise.

## Current phase

Phase 0 — Inspect the fresh Laravel project.

## Current state

Development has not started yet.

Intended direction:

- Laravel 13;
- PHP 8.4+;
- official Livewire Starter Kit;
- Livewire 4;
- Flux UI;
- Tailwind CSS 4;
- PostgreSQL;
- Laravel Sail;
- Pest;
- GitHub.

Future tools/services that should be introduced only when useful:

- Redis;
- Laravel Queue;
- Laravel Cache;
- Adminer;
- Mailpit;
- Postman.

The previous plain-PHP implementation is a behavioral reference only.

## Current learning task

Not assigned yet.

Codex should inspect the real repository and assign the first small task according to `AGENTS.md`.

## Last completed task

None.

## What I learned

Nothing recorded yet.

## Decisions made

- Use Laravel 13.
- Use official Livewire Starter Kit.
- Use Livewire 4.
- Use Flux UI instead of Bootstrap/daisyUI.
- Use PostgreSQL instead of MySQL.
- Use Laravel Sail for development.
- Do not configure Redis until there is a real use-case.
- Do not add Mailpit unless email functionality is needed.
- Adminer is optional development tooling.
- Use Postman as an HTTP exploration tool, not a replacement for tests.
- Keep Dahua webhook handling outside Livewire.
- Introduce queues only after understanding the synchronous webhook.
- Do not port homemade framework infrastructure from the old PHP project.

## Open questions

Resolve only when needed:

- exact first Camera schema;
- Dahua webhook authentication contract;
- required event/rule fields;
- Dahua snapshot behavior;
- MAX API request workflow;
- which runtime settings belong in DB vs config/env;
- production deployment target.

## Next likely steps

1. Finish creating the Laravel project.
2. Add these learning/docs files.
3. Commit the initial state.
4. Start Codex.
5. Let Codex inspect the real project before task #1.

## Last session handoff

Date: not started.

Summary:

- Learning workflow prepared.
- No application feature has been implemented.

Next action:

- Start Codex from repository root using `START_CODEX.txt`.
