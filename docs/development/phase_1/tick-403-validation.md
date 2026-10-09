# TICK-403 validation

## Responsive dashboard

The dashboard presents each vacancy as a compact card below 768px. Cards retain
the same vacancy details, status, and actions as the tablet-and-desktop table.
At 768px and above the semantic table remains the visible dashboard result.

Manually verify authenticated pages at 320px, 390px, 412px, 768px, and 1440px:

1. Open and close the mobile navigation, then move through the pipeline with
   keyboard and pointer input.
2. Confirm the Profile link remains visible in the authenticated header and Sign out is accessible at the bottom of the mobile menu.
3. Apply and reset dashboard filters; confirm the result cards or table update.
4. Open a vacancy action menu, tab through it, and use a non-destructive action.
5. Confirm `document.documentElement.scrollWidth` equals `document.documentElement.clientWidth` on the dashboard, vacancy create/edit/detail, company/recruiter list and form, and profile pages. Horizontal scrolling is permitted only inside the pipeline and Markdown tables/code blocks.

### Regression found after the original validation

The original TICK-403 verification reported the dashboard responsive, but a
Pixel 8 viewport at 412px exposed a page-level overflow: the document was
921px wide. The dashboard filter grid's intrinsic control widths expanded its
parent grid track, which then widened the pipeline and results panels.

The responsive repair makes dashboard tracks shrinkable, constrains form
controls to their containers, stacks dashboard filters on phone widths, and
applies the same shrink/wrap safeguards to authenticated-page action and list
layouts.

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
docker compose exec -T app php vendor/bin/paratest tests/Functional --filter 'DashboardTest|LayoutTest|VacancyIndexesTest'
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

The repaired authenticated dashboard, vacancy create/detail/edit, company and
recruiter list/create, and profile pages were checked at 320px, 390px, 412px,
768px, and 1440px. At every width,
`document.documentElement.scrollWidth` equalled `clientWidth`. The dashboard
filters stack into one column below 768px, while the pipeline remains locally
horizontally scrollable.

The follow-up focused dashboard and layout checks completed with 11 tests and
240 assertions. The original dashboard and index checks completed with 8 tests
and 214 assertions. The full suite completed with 142 tests and 1,082 assertions;
coverage was 93.38% (1,722 of 1,844 lines). PHPStan, PHP-CS-Fixer check,
container lint, Doctrine schema validation, Twig lint, and `git diff --check`
also passed. The migration was applied to development, the base test database,
and all 12 ParaTest worker databases.

## Follow-up verification — 2026-10-09

The authenticated header now keeps Profile visible below 768px while retaining
the compact mobile Sign out behavior. `ProfileSettingsTest` passed with 7 tests
and 76 assertions, including the Profile-link regression check.

The mobile menu now provides a full-width, CSRF-protected Sign out control at
its bottom. It remains reachable by scrolling within the sidebar when needed.
The focused `SecurityTest|ProfileSettingsTest` check passed with 14 tests and
118 assertions, including sidebar-specific logout submission.
