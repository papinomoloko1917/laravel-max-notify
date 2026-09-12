# max-notify

Learning-oriented Laravel application that will receive Dahua camera events and notify configured MAX Messenger recipients with camera snapshots.

The project is being built incrementally. The roadmap describes future direction; it does not mean queues, Redis, webhooks, or external API integrations are already implemented.

## Current status

- Laravel 13 with the official Livewire Starter Kit;
- Livewire 4, Flux UI, and Tailwind CSS 4;
- PostgreSQL 18;
- Laravel Sail with Laravel, PostgreSQL, and local Adminer services;
- Pest, Pint, and Larastan;
- minimal Camera migration, Eloquent model, factory, and focused database test;
- Client migration, Eloquent model, factory, and initial database tests.

The Camera ↔ Client many-to-many relationship is currently in progress. Client management UI, Dahua/MAX integrations, queues, Redis, duplicate protection, and event history are not implemented yet.

See [Development Handoff](docs/DEVELOPMENT.md) for the exact current state and known quality-baseline issues.

## Local development

Prerequisites:

- Git;
- Docker with Docker Compose;
- PHP and Composer for the initial dependency installation.

Initial setup:

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Development services:

- application: `http://localhost` by default;
- Adminer: `http://localhost:8080` by default;
- PostgreSQL host from Sail containers: `pgsql`.

Keep `.env`, camera credentials, API tokens, and production secrets out of Git.

## Focused Camera checks

```bash
./vendor/bin/sail artisan test tests/Feature/Models/CameraTest.php
./vendor/bin/sail pint --test \
  app/Models/Camera.php \
  database/factories/CameraFactory.php \
  tests/Feature/Models/CameraTest.php
```

The full quality command is:

```bash
./vendor/bin/sail composer test
```

It currently exposes pre-existing Starter Kit/localization baseline issues recorded in `docs/DEVELOPMENT.md`.

## Project documentation

- [Learning roadmap](docs/ROADMAP.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Development handoff](docs/DEVELOPMENT.md)
- [Codex and Git workflow](docs/CODEX_WORKFLOW.md)
- [Teaching rules](AGENTS.md)
