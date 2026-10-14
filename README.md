# max-notify

Laravel application that receives Dahua camera events and notifies configured MAX Messenger recipients with camera snapshots.

The project is being built incrementally. The roadmap describes future direction rather than features that must all be installed in advance.

## Current status

- Laravel 13 with the official Livewire Starter Kit;
- Livewire 4, Flux UI, and Tailwind CSS 4;
- PostgreSQL 18;
- Laravel Sail with Laravel, PostgreSQL, and local Adminer services;
- Pest, Pint, and Larastan;
- authenticated Camera and Client administration with tested many-to-many assignments;
- authenticated Dahua IVS webhook with per-Camera notification windows;
- tested Dahua snapshot client using Digest authentication and explicit request limits;
- tested MAX Messenger client for upload-URL creation, JPEG upload, and image-message delivery;
- asynchronous snapshot delivery through the PostgreSQL-backed Laravel queue;
- atomic duplicate-event suppression through the PostgreSQL-backed Laravel cache;
- persisted CameraEvent history, operational Dashboard, and read-only queue-health counts;
- a published Sail 8.5 image with the trusted CA chain required by the current MAX HTTPS endpoints.

Camera and Client administration, including many-to-many assignment editing and validated Camera notification-window configuration, is implemented. The IVS endpoint authenticates active Cameras through HTTP Basic, validates static metadata, applies the configured notification window, suppresses immediate duplicates, persists a pending CameraEvent, and dispatches slow Dahua/MAX work to the database queue. The worker retrieves and uploads one snapshot and sends it to every assigned Client while updating event history. Redis remains deliberately deferred because PostgreSQL currently satisfies the observed queue and cache requirements.

See [Development Handoff](docs/DEVELOPMENT.md) for the exact current state and verification baseline.

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

It runs Pint, Larastan, and the full automated test suite. The current baseline passes without ignored static-analysis findings.

## Project documentation

- [Development roadmap](docs/ROADMAP.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Development handoff](docs/DEVELOPMENT.md)
- [Operations runbook](docs/OPERATIONS.md)
- [Codex and Git workflow](docs/CODEX_WORKFLOW.md)
- [Agent rules](AGENTS.md)
