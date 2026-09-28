# TICK-304 validation

Run the dashboard coverage and project checks from the application container:

```bash
docker compose exec -T -e APP_ENV=test app php vendor/bin/phpunit tests/Functional/DashboardTest.php tests/Application/Vacancy/VacancyLifecycleHandlerTest.php
docker compose exec -T -e APP_ENV=test app composer ci:coverage
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
docker compose exec -T app php bin/console doctrine:schema:update --dump-sql
```

Sign in and visit `/app`. The active pipeline chevrons appear above a paginated
overview. Search by title, company, or technology; filter by status, excitement,
work mode, salary fit, and archived state. The table shows 20 rows per page and
retains filters while paging or selecting a chevron. Each row exposes owner-scoped
status changes, archive or restore, and a CSRF-protected delete confirmation.
