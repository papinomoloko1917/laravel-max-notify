# max-notify — Operations Runbook

This runbook covers the current PostgreSQL-backed queue. It is intended for trusted operators with shell and database access, not for end users of the administration UI.

## Failed-job policy

- The Dashboard shows only the number of rows in `jobs` and `failed_jobs`.
- Queue payloads and exception text are restricted operational data. Do not expose them through the web UI, paste them into public chats, or attach them to public issues.
- Retain failed-job diagnostics for 30 days (`720` hours). This is long enough for the current low-volume installation to investigate delayed reports without keeping exception details indefinitely.
- Failed-job retention remains a reviewed manual maintenance action; production Scheduler currently automates only CameraEvent retention.

## Investigating a failure

1. Confirm the failed-job count on the Dashboard.
2. List failed jobs from the trusted application host:

   ```bash
   ./vendor/bin/sail artisan queue:failed
   ```

3. Record the failed-job UUID, timestamp, connection, queue, and job class.
4. Correlate it with a `camera_events` row. `ProcessCameraEvent` stores the related CameraEvent ID in its queue payload; inspect raw payload or exception data only through protected database access such as local Adminer or `psql`.
5. Use `camera_events.failure_type`, recipient totals, application logs, and the protected failed-job exception to identify the cause. Never copy credentials, authorization headers, tokens, full payloads, or full exception traces into tickets or chat.
6. Correct the underlying configuration, network, NVR, MAX API, or application problem before closing the incident.

## Why retry is prohibited

Do not run either of these commands for `ProcessCameraEvent`:

```bash
./vendor/bin/sail artisan queue:retry <uuid>
./vendor/bin/sail artisan queue:retry all
```

The job sends messages sequentially to multiple Clients. If delivery fails after one or more successful sends, retrying the whole job starts again from the first Client and can duplicate notifications. `$tries = 1` is an intentional safety boundary, not a temporary worker setting.

Retry can be introduced only after delivery is tracked per recipient and each send has an idempotency guarantee or an equivalent safe resume point.

## Resolving and removing records

After the cause has been investigated and recorded without sensitive data, remove the individual diagnostic record:

```bash
./vendor/bin/sail artisan queue:forget <uuid>
```

This deletes only the selected `failed_jobs` row. It does not delete or change the corresponding `camera_events` history.

Prune diagnostics older than 30 days during planned maintenance:

```bash
./vendor/bin/sail artisan queue:prune-failed --hours=720
```

Review the failed-job list before pruning. Do not use `queue:flush` as routine cleanup because it deletes every failed-job diagnostic, and do not use `queue:clear` because it deletes pending work.

## Camera-event history retention

Processed `camera_events` rows (`completed`, `skipped`, and `failed`) are retained for 30 days by default. The development scheduler runs the following command daily at 03:00:

```bash
./vendor/bin/sail artisan camera-events:prune
```

Set a different positive period with `CAMERA_EVENT_RETENTION_DAYS`. Pending rows are deliberately excluded because a queued job may still reference them. The authenticated event-history page also offers a confirmed “Очистить обработанную историю” action; it applies the same terminal-status boundary and never clears the Laravel queue or `failed_jobs` diagnostics.

## Production commands

Production runs one supervised database worker and one Laravel Scheduler container through `compose.production.yaml`. Replace the Sail prefix in the commands above with:

```bash
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan
```

For example, list failures with `... artisan queue:failed` and remove one investigated diagnostic with `... artisan queue:forget <uuid>`. Failed-job pruning remains a reviewed operator action; only CameraEvent retention is scheduled. Monitoring, the off-device backup schedule, and a restore rehearsal remain site-specific follow-up described in `docs/DEPLOYMENT.md`.

## Notification latency

The database worker polls for new jobs using `QUEUE_SLEEP=1`. After changing this value, recreate the queue container; restarting only the Laravel worker process does not change its Compose command:

```bash
docker compose --env-file .env.production -f compose.production.yaml up -d --force-recreate queue
```

For a controlled event, compare `camera_events.created_at` with `processed_at`. This measures processing after Laravel accepted the webhook. It does not measure a delay inside the Camera/NVR before the HTTP request, because the current static Dahua callback does not include the device's detection timestamp. If `jobs` builds up above zero during simultaneous events, investigate worker capacity; do not add Redis or workers based only on one event's external Dahua/MAX request time.

The webhook now captures one JPEG before queue dispatch. A normal response therefore waits for the Camera/NVR snapshot request, but not for MAX. HTTP `503 Snapshot unavailable` means no job was dispatched; the corresponding event is terminally failed and the duplicate claim was released so the device or operator can retry. Pending jobs own files under the private `camera-event-snapshots/` storage directory. Do not clear that directory while jobs are pending. Normal success, skip, exception, and queue failure paths delete their own file; persistent files with no matching pending job indicate an interrupted handoff and should be investigated before manual removal.
