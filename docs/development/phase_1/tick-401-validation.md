# TICK-401 validation

## Domain facts and outbox

Aggregate writes record named domain facts rather than a generic change event. The
outbox payload is deliberately minimized because records are retained indefinitely.
Company and recruiter facts contain organization metadata only, while technology facts
contain name, slug, and category. Vacancy create and detail facts contain status, work
mode, contract type, and application source; transition facts contain only the from
status, to status, and transition. Archive and restore facts contain the archived flag.
Registration, preferences, scratchpad, next-action, and deletion facts carry no
changed properties. Contact details, titles, salaries, URLs, resume metadata,
preferences, and related aggregate identifiers are excluded. TICK-402 intentionally
retains a normalized status-transition note so its asynchronous audit handler can
preserve the user-visible timeline. Resume-validation requests retain only a random
candidate ID, never the filename, storage path, MIME metadata, or file contents.

Lingoda Domain Events persists the events during Doctrine's transaction into its
outbox storage. The configured `outbox://` Messenger transport is consumed by the
Compose `worker` service. Delivery is at least once, so future handlers must use the
stable outbox message identity for idempotency. Published operational metadata remains
in the outbox indefinitely; pruning is disabled.

Outbox event documents use JSON rather than PHP serialization. Each document contains
the concrete `NamedDomainEvent` type, `eventVersion`, aggregate identifier, occurrence
timestamp, and the minimized changed-properties payload. `Version20261002123000` changes
the outbox column to JSON; it assumes there are no existing outbox records. New event
versions need an explicit decoder path before they are written.

## Identifier migration

`Version20260930120000` is the sole UUID conversion migration. It generates a Symfony
`Uuid::v7()` value for every existing root row, fills and verifies temporary relation
mappings, then replaces keys and foreign keys. A retry resumes only when the relation
checkpoint is complete; it never joins through a removed legacy `uuid` column. It is a
production maintenance migration: take a verified database backup and run it once
while writes are stopped. New roots also generate UUIDv7 in PHP. The migration has no
automatic down path; use the backup to roll back.

`Version20261001120000` creates the Lingoda outbox table. The Carbon Doctrine type
causes one known metadata-only timestamp comparison for that vendor entity. Use the
scoped schema command below: it ignores Doctrine's own migration metadata table,
permits exactly that comparison, and fails for any other entity schema SQL.

## Commands

```bash
docker compose exec -T app php bin/console lint:container --no-debug
docker compose exec -T app php bin/console doctrine:migrations:status --no-interaction
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app composer tests
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
```

Apply the consolidated migrations to development, the base test database, and every
ParaTest database before running the suite.

On 2026-10-02, a disposable legacy `test99` database containing a user and vacancy was
migrated from `Version20260928120000` through the UUID migration. Both resulting keys
were 16-byte values and the `FK_VACANCIES_USER` foreign key remained present.
