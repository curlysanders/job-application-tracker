# TICK-403 validation

## Responsive dashboard

The dashboard presents each vacancy as a compact card below 768px. Cards retain
the same vacancy details, status, and actions as the tablet-and-desktop table.
At 768px and above the semantic table remains the visible dashboard result.

Manually verify the authenticated dashboard at 390px, 768px, and 1440px:

1. Open and close the mobile navigation, then move through the pipeline with
   keyboard and pointer input.
2. Apply and reset dashboard filters; confirm the result cards or table update.
3. Open a vacancy action menu, tab through it, and use a non-destructive action.
4. Confirm page-level horizontal overflow does not occur at 390px.

## Query indexes

`Version20261005100000` replaces the standalone vacancy owner, status, and
date-added indexes with three composite indexes:

- `IDX_VACANCIES_DASHBOARD` for an owner's active or archived dashboard sorted
  by newest vacancy.
- `IDX_VACANCIES_DASHBOARD_STATUS` for status-filtered dashboard and pipeline
  queries.
- `IDX_VACANCIES_REMINDERS` for a user's ordered due reminders.

Apply the migration to development, the base test database, and every ParaTest
worker database before running the suite. Confirm the live schema with:

```bash
docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T app php bin/console dbal:run-sql 'SHOW INDEX FROM vacancies'
```

## Automated checks

```bash
docker compose exec -T app php vendor/bin/paratest tests/Functional/DashboardTest.php tests/Functional/VacancyIndexesTest.php
docker compose exec -T app composer tests
docker compose exec -T app composer ci:coverage
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
docker compose exec -T app php bin/console lint:container --no-debug
docker compose exec -T app composer ci:doctrine-schema
docker compose exec -T app php bin/console lint:twig templates
```

## Deferred deployment validation

No Synology DS416play deployment or FrankenPHP worker memory measurement is part
of TICK-403. Measure container memory under realistic peak execution only after
the production deployment configuration exists.

## Verification result — 2026-10-05

The focused dashboard and index checks completed with 8 tests and 214
assertions. The full suite completed with 142 tests and 1,082 assertions;
coverage was 93.38% (1,722 of 1,844 lines). PHPStan, PHP-CS-Fixer check,
container lint, Doctrine schema validation, Twig lint, and `git diff --check`
also passed. The migration was applied to development, the base test database,
and all 12 ParaTest worker databases.
