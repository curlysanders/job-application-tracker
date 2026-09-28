# TICK-303 validation

Run the focused dashboard test and the project checks from the application container:

```bash
docker compose exec -T app php bin/phpunit tests/Functional/DashboardTest.php
docker compose exec -T app composer ci:coverage
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
```

Sign in and visit `/app`. The six main pipeline stages show the signed-in user's
non-archived counts. Selecting a stage replaces only the vacancy table and updates
the URL; browser back and forward restore the selected result. With JavaScript
disabled, each stage link loads the same filtered dashboard page.
