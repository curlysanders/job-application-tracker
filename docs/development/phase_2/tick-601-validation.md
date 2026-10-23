# TICK-601 validation

## Vacancy extraction review

Authenticated users can paste vacancy text at `/vacancies/extract`. The request is queued through the encrypted asynchronous analysis flow from TICK-502; the status page exposes only pending, completed, or generic failed states and polls while pending.

Completed output is reviewable only by its owner. A proposal can be selected only when its source excerpt occurs in the pasted text, its confidence is at least 80%, and its value is compatible with the existing vacancy form. Unsupported enums, currencies, dates, salary values, ungrounded excerpts, and unknown technology names are omitted. Company evidence is informational and never changes a company or recruiter record.

Selected values are stored as a tenant-owned encrypted authoring draft for seven days. The pasted source is included as full vacancy text. The draft is deleted only after the existing new-vacancy form successfully creates a vacancy; the daily `app:ai:purge-expired-analysis` command removes expired analysis data and authoring drafts.

## Automated checks

```bash
docker compose exec -T app php vendor/bin/paratest tests/Application/Ai tests/Functional/VacancyAuthoringTest.php
docker compose exec -T app php bin/console lint:container --env=test --no-debug
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app composer tests
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
```
