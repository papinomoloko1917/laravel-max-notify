# max-notify — Operations Runbook

This runbook covers the current PostgreSQL-backed queue. It is intended for trusted operators with shell and database access, not for end users of the administration UI.

## Failed-job policy

- The Dashboard shows only the number of rows in `jobs` and `failed_jobs`.
- Queue payloads and exception text are restricted operational data. Do not expose them through the web UI, paste them into public chats, or attach them to public issues.
- Retain failed-job diagnostics for 30 days (`720` hours). This is long enough for the current low-volume installation to investigate delayed reports without keeping exception details indefinitely.
- Retention remains a manual maintenance action until the production scheduler lifecycle is designed in Phase 25.

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

## Production follow-up

Phase 25 must define the persistent worker, scheduler, monitoring, database backup, and restore lifecycle for the actual deployment target. Only then should the 30-day prune command be scheduled automatically.
