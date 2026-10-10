# max-notify

Learning-oriented Laravel application that will receive Dahua camera events and notify configured MAX Messenger recipients with camera snapshots.

The project is being built incrementally. The roadmap describes future direction; it does not mean queues, Redis, webhooks, or external API integrations are already implemented.

## Current status

- Laravel 13 with the official Livewire Starter Kit;
- Livewire 4, Flux UI, and Tailwind CSS 4;
- PostgreSQL 18;
- Laravel Sail with Laravel, PostgreSQL, and local Adminer services;
- Pest, Pint, and Larastan;
- authenticated Camera and Client administration with tested many-to-many assignments;
- synchronous authenticated Dahua IVS webhook with per-Camera notification windows;
- tested Dahua snapshot client using Digest authentication and explicit request limits;
- tested MAX Messenger client for upload-URL creation, JPEG upload, and image-message delivery;
- a published Sail 8.5 image with the trusted CA chain required by the current MAX HTTPS endpoints.

Camera and Client administration, including many-to-many assignment editing and validated Camera notification-window configuration, is implemented. A minimal synchronous IVS endpoint authenticates active Cameras through HTTP Basic, validates static metadata, and applies the configured per-Camera notification window. Separate Dahua and MAX clients are covered with HTTP fakes and have completed one controlled real snapshot-to-message smoke run in 2,569 ms. They remain disconnected from the webhook. Queue jobs, Redis, duplicate protection, and event history are not implemented.

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
./vendor/bin/sail build --no-cache laravel.test
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

This installation expects `APP_TIMEZONE=Europe/Moscow`. Dahua snapshot access uses the local-only `DAHUA_BASE_URL`, `DAHUA_USERNAME`, and `DAHUA_PASSWORD` values described by `.env.example`; the base URL must contain only the recorder origin, without `/cgi-bin`. MAX access uses the local-only `MAX_TOKEN`. Never commit real credentials or tokens. The default Dahua connection and overall request limits are 3 and 10 seconds.

The application image is built from the committed `docker/8.5` runtime. Rebuild `laravel.test` after pulling changes to that Dockerfile or its certificates. TLS verification must remain enabled.

## Focused integration checks

```bash
./vendor/bin/sail artisan test tests/Feature/Models/CameraTest.php
./vendor/bin/sail artisan test tests/Feature/DahuaWebhookTest.php
./vendor/bin/sail artisan test tests/Feature/Services/DahuaClientTest.php
./vendor/bin/sail artisan test tests/Feature/Services/MaxClientTest.php
./vendor/bin/sail pint --test \
  app/Services/DahuaClient.php \
  tests/Feature/Services/DahuaClientTest.php \
  config/services.php
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
