# TICK-402 validation

## Asynchronous work

Status transitions now persist the vacancy and its `VacancyStatusTransitioned`
outbox event during the HTTP request. `RecordVacancyStatusTransition` receives that
event from the existing `outbox` worker and writes the history row afterwards. Its
unique outbox-record ID makes a redelivery a no-op.

Resume uploads are staged under the user's private `resumes/{user-id}/pending/`
path. The current active resume remains downloadable while
`ValidateUploadedResume` checks the candidate's stored PDF or DOCX structure. A
passing candidate becomes active and the previous active object is removed. A failed
candidate is removed, the current active resume is retained, and the profile shows a
safe failure message.

Outbox event records are retained indefinitely. Transition notes are therefore an
intentional TICK-402 exception to the earlier minimized-payload policy. Resume
validation records contain only the candidate validation UUID.

## Reproduction

```bash
docker compose exec -T app composer tests
docker compose exec -T app composer ci:coverage
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
docker compose exec -T app php bin/console lint:container --no-debug
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app php bin/console lint:twig templates
```

Apply `Version20261002133000` to development, the base test database, and every
ParaTest worker database before the suite. It adds the status-history outbox delivery
key and staged resume fields.

## Verification result

The final Docker run completed 141 tests and 1,072 assertions. It reported 93.38%
line coverage (1,723 of 1,845 lines), exceeding the 92% project gate.

## HTTP timing measurement

Start the isolated `benchmark` profile against the development database. It runs from
a read-only source mount with private cache, log, and Caddy files; this prevents stale
development cache state from affecting the production measurement. The profile loads
the uncommitted `.env.local` file so production mode receives the local `APP_SECRET`.

```bash
docker compose --profile benchmark up -d benchmark
```

With an existing account and an owned bookmarked vacancy, run the automated benchmark
from an interactive terminal. The password is prompted without echoing it:

```bash
docker compose --profile benchmark exec benchmark php scripts/benchmark-vacancy-status.php \
  --base-url=http://127.0.0.1 \
  --email=user@example.test \
  --vacancy=00000000-0000-0000-0000-000000000000
```

It warms the edit endpoint, obtains each rendered CSRF token, sends 30 alternating
`start_applying` and `undo_start_applying` requests, and prints every `app;dur`
sample plus median, p95, and maximum. It fails when any sample is 15 ms or higher;
after the even number of transitions the vacancy remains bookmarked. Confirm separately
that the worker has created one status-history row per transition, then stop the
isolated service with `docker compose --profile benchmark stop benchmark`.

## HTTP timing result — 2026-10-04

The isolated production benchmark completed all 30 alternating status transitions
below the 15 ms acceptance threshold. The warm-request median was 9.113 ms, p95 was
12.124 ms, and the maximum was 14.175 ms.
