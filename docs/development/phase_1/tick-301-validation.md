# TICK-301 Validation

## Reproduction

```bash
docker compose exec -T -e APP_ENV=test app php vendor/bin/phpunit tests/Application/Vacancy/TransitionVacancyStatusHandlerTest.php tests/Functional/VacancyStatusWorkflowTest.php
docker compose exec -T app php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec -T app php vendor/bin/phpstan analyse
docker compose exec -T app php vendor/bin/deptrac analyse --fail-on-uncovered
docker compose exec -T app composer ci:coverage
```

The `vacancy_status` state machine uses the persisted `VacancyStatus` enum as
its marking. It allows the documented forward pipeline, one-stage corrections
while a vacancy is active, and final outcome statuses. The edit page renders
only currently enabled CSRF-protected transition buttons. The owner-scoped
application command applies a transition through the workflow port and saves
the vacancy.

## Results — 2026-09-25

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Illegal status jumps are prevented | `VacancyStatusWorkflowTest` verifies enabled transitions for every state and confirms `BOOKMARKED → ACCEPTED` is rejected. | Pass |
| Status changes work programmatically and through the UI | `TransitionVacancyStatusHandlerTest` covers the owner-scoped command path; `VacancyStatusWorkflowTest` covers edit-page buttons, CSRF, owner isolation, and persistence. | Pass |

The complete suite passed with 81 tests and 566 assertions. Overall line
coverage was 94.11%, exceeding the enforced 90% minimum. PHP CS Fixer, PHPStan,
Deptrac, Doctrine schema validation, Twig linting, and container linting passed.

`Version20260925130000` establishes readable names for the company and
recruiter indexes plus both join-table foreign keys. It accepts either the
earlier descriptive or Doctrine-generated names without changing data or
schema semantics. The implicit many-to-many supporting indexes retain
Doctrine's generated names because that association mapping has no explicit
index-name configuration. Fresh, legacy-name, and generated-name database
paths passed schema validation.
