# TICK-103 validation

## Implementation

- `User` stores optional minimum preferred salary, maximum one-way commute, and
  preferred transport mode. Migration `Version20260918120000` adds the three
  nullable database columns.
- Gross monthly salary is an EUR-only `GrossMonthlySalary` value object backed
  by Brick Money and BCMath. It preserves decimal precision and rejects
  non-positive values. `User::evaluatesSalary()` returns `MEETS_TARGET`,
  `BELOW_TARGET`, or `NOT_CONFIGURED` when no salary preference has been set.
- Authenticated users can update preferences at `/app/profile`. The Symfony
  `EnumType` renders the transport enum; form validation rejects non-positive
  salaries and commute durations. A successful update dispatches the
  `UpdateUserPreferences` command and redirects back to the profile page.
- Command handlers implement the application-owned `CommandHandler` marker;
  Messenger tags that marker centrally for the `command.bus`.

## Repeatable checks

Run from the repository root after starting Docker Compose. ParaTest uses a
separate database per worker, so provision and migrate the worker databases
first when setting up a new local database volume:

```bash
docker compose exec app sh -lc 'php bin/console doctrine:database:create --if-not-exists --env=test && php bin/console doctrine:migrations:migrate --no-interaction --env=test && for token in $(seq 1 "$(nproc)"); do TEST_TOKEN="$token" php bin/console doctrine:database:create --if-not-exists --env=test && TEST_TOKEN="$token" php bin/console doctrine:migrations:migrate --no-interaction --env=test; done'
docker compose exec app composer tests
docker compose exec app composer ci:phpstan
docker compose exec app composer ci:deptrac
docker compose exec app composer ci:php-cs-fixer
docker compose exec app php bin/console doctrine:schema:validate --skip-sync --env=dev
```

## Results — 2026-09-18

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| User can update salary, commute, and transport preferences | Functional test authenticates a user, submits `/app/profile`, then reloads the user and verifies all three persisted values. | Pass |
| Form validates positive monetary amounts and commute times | Functional test submits `0.00` salary and `0` minutes, then verifies the HTTP 422 response and both validation messages. | Pass |
| Salary comparison is precise and safe when unset | Domain test compares `4500.00` and `4499.99` exactly, verifies `NOT_CONFIGURED`, and rejects zero salary and commute values. | Pass |

The complete suite passed with 19 tests and 111 assertions. PHPStan, Deptrac,
PHP CS Fixer, and Doctrine mapping validation also passed.
