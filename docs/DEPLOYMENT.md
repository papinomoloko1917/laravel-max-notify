# max-notify — Deployment Plan

Status: target architecture agreed in principle; production files are not implemented yet.

## Confirmed target

- hardware: MiniPC MA01, Intel N150, 16 GB RAM, 512 GB storage;
- operating system: Ubuntu Server 26.04.1 LTS;
- location: on premises next to the Dahua NVR;
- network: application server and NVR are on the same LAN;
- public domain: none;
- server state: clean installation, so the required runtime may be installed deliberately;
- external requirement: the server still needs outbound DNS and verified HTTPS access to the MAX API and package/image repositories;
- timezone: `Europe/Moscow`, with host clock synchronization enabled.

The hardware is sufficient for the current low-volume installation. Start with one web application instance and one queue worker; add concurrency only after measured queue delay demonstrates a need.

## Chosen runtime direction

Use Docker Engine from Docker's official Ubuntu repository plus the Docker Compose plugin. Ubuntu 26.04 LTS is an officially supported Docker Engine target.

Do not deploy the current `compose.yaml`. It is a Sail development environment and currently includes development-only behavior:

- application source bind-mounted from the host;
- the PHP development server (`artisan serve`);
- a published Vite port;
- PostgreSQL published to the host;
- publicly reachable Adminer;
- Xdebug/Sail-specific environment;
- development user-ID mapping.

Create a separate production image and Compose file after the remaining network decisions are known. The production topology should contain:

1. a reverse proxy / web server;
2. an immutable application image running PHP-FPM;
3. one container from the same application image running the database queue worker;
4. one container from the same application image running Laravel's scheduler;
5. PostgreSQL with a named persistent volume and no published database port.

Adminer and Node/Vite development servers must not run in production. Frontend assets are built while creating the application image.

## Queue and scheduler

The initial worker topology is one process for the `database` connection and `default` queue. Its effective command should preserve the job's existing timeout and single-attempt policy, for example:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=1 --timeout=80 --max-time=3600
```

The container/process stop grace period must be longer than the longest job timeout. The worker must restart automatically after exit and be restarted gracefully after each deployment because Laravel queue workers are long-lived and do not automatically load changed code.

Run exactly one scheduler process. Its first production responsibility will be the documented 30-day failed-job pruning policy. Do not schedule pruning until the production Compose lifecycle is implemented and verified.

## Network exposure

Only the reverse proxy may publish host ports. PostgreSQL, application PHP, worker, and scheduler remain on an internal Docker network.

The NVR needs access only to the Dahua webhook path. If the recorder cannot validate a private HTTPS certificate, provide a dedicated HTTP listener restricted by firewall and reverse-proxy rules to the NVR source IP and `/webhooks/dahua`; do not expose the administration UI over that listener.

The administration UI carries login credentials and session cookies and should use HTTPS even on the LAN. A public domain is not required: a private CA can issue a certificate for a stable local hostname or IP address, with that CA installed on administrator devices. Plain HTTP for the administration UI requires an explicit acceptance of the LAN credential/session interception risk.

Docker-published ports can bypass uncomplicated UFW expectations. Host firewall design must account for Docker's `DOCKER-USER` chain rather than assuming a UFW deny rule protects published container ports.

## Production environment

Production values belong in an uncommitted, root/operator-readable environment file. At minimum:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- a stable `APP_KEY` that is backed up securely;
- an `APP_URL` matching the final local HTTPS hostname/IP;
- strong PostgreSQL credentials;
- `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, and `SESSION_DRIVER=database`;
- the real Dahua and MAX integration settings;
- `LOG_CHANNEL=stderr` or another container-aware channel with host-level rotation;
- secure session cookies when the administration UI uses HTTPS.

Never bake `.env` or real credentials into the image or Git history.

## Persistent data and backup boundary

The PostgreSQL volume is the primary persistent application state. A Docker volume on the same 512 GB SSD is not a backup.

Phase 25 must add and restore-test a scheduled PostgreSQL logical backup copied to a different physical device or protected network destination. The final backup schedule, encryption, retention, and destination remain open until that destination is selected. The production environment file and `APP_KEY` also require a secure recovery copy separate from the MiniPC.

## Deployment lifecycle to implement

The eventual deployment procedure must be explicit and repeatable:

1. fetch the reviewed Git revision;
2. build the immutable application image and frontend assets;
3. run the automated quality gate before replacing the running release;
4. place the application in maintenance mode when a migration requires it;
5. apply migrations with `--force`;
6. refresh Laravel production caches;
7. replace/reload web, worker, and scheduler containers;
8. gracefully restart queue workers;
9. leave maintenance mode;
10. verify `/up`, authenticated administration access, queue health, and a controlled end-to-end event;
11. retain a rollback path for the previous image and a database restore path.

## Information still required

Before production files are created, record:

- the MiniPC's reserved/static LAN IP;
- the NVR's reserved/static LAN IP;
- which devices/subnet may open the administration UI;
- whether a private-CA certificate can be installed on those administrator devices;
- whether the NVR supports HTTPS with a private CA; otherwise confirm the restricted HTTP webhook listener;
- the router/firewall arrangement and whether inbound access from outside the LAN is blocked;
- the off-device PostgreSQL backup destination;
- whether power-loss protection (UPS) is available.

## Primary references

- [Docker Engine installation on Ubuntu](https://docs.docker.com/engine/install/ubuntu/)
- [Laravel 13 deployment](https://laravel.com/docs/13.x/deployment)
- [Laravel 13 queues](https://laravel.com/docs/13.x/queues)
- [Laravel 13 task scheduling](https://laravel.com/docs/13.x/scheduling)
