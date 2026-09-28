# TICK-302 Validation

## Reproduction

```bash
docker compose exec -T -e APP_ENV=test app php vendor/bin/phpunit tests/Domain/Vacancy/VacancyStatusHistoryTest.php tests/Infrastructure/Workflow/RecordVacancyStatusTransitionTest.php tests/Functional/VacancyStatusWorkflowTest.php
docker compose exec -T app php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec -T app php vendor/bin/phpstan analyse
docker compose exec -T app php vendor/bin/deptrac analyse --fail-on-uncovered
docker compose exec -T app php bin/console doctrine:schema:validate
docker compose exec -T app php bin/console lint:twig templates
docker compose exec -T app php bin/console lint:container
docker compose exec -T app composer ci:coverage
```

`VacancyStatusHistory` stores each successful `vacancy_status` state-machine
transition with its typed source and target statuses, optional normalized note,
and immutable timestamp. The workflow transition listener schedules the audit
record in the same Doctrine flush as the owner-scoped vacancy transition.

The vacancy edit page opens an accessible modal for an optional note before a
status change. Its server-rendered transition forms remain available when
JavaScript is unavailable. The timeline renders newest first beneath the status
controls, including each transition timestamp and any supplied note.

## Results — 2026-09-28

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Every status change writes an audit record with timestamp and target status | `RecordVacancyStatusTransitionTest` verifies the listener maps workflow source, target, and note into history. `VacancyStatusWorkflowTest` verifies the HTTP transition persists the record and normalized note. | Pass |
| Transition timeline displays cleanly on the vacancy edit page | `VacancyStatusWorkflowTest` verifies the optional-note modal, empty timeline state, and rendered status transition with its note after a successful change. | Pass |

`Version20260928120000` creates `vacancy_status_history` with its cascade
foreign key and chronological vacancy index. The migration was applied to the
local development database, test database, and all 12 ParaTest worker
databases; Doctrine schema validation then reported no differences.

The complete suite passed with 84 tests and 590 assertions. Overall line
coverage was 94.41%, exceeding the enforced 90% minimum. PHP CS Fixer, PHPStan,
Deptrac, Twig linting, container linting, and `git diff --check` passed.
