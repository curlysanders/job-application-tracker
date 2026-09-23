# TICK-202 Validation

## Reproduction

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console app:tech-stack:seed
docker compose exec app composer tests
docker compose exec app composer ci:coverage
docker compose exec app composer analyse
docker compose exec app composer ci:php-cs-fixer
docker compose exec app php bin/console doctrine:schema:validate
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console lint:container
```

The seed command is idempotent: it inserts the standard catalogue only when a
slug is absent and leaves existing tags unchanged. The reusable Technology tags
preview is available in development and test at `/_components`. It provides a
multi-select search for known technologies and a per-tag new-technology row
whose category is required, suggested from existing categories, and may be a
new value.

TICK-204 will map the reusable form data to its Vacancy command. Its handler
will resolve existing slugs and create each new tag from its required name and
category before attaching the resulting entities to the Vacancy aggregate.

## Results — 2026-09-22

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Pre-seeded tags are selectable through the UI | The protected autocomplete endpoint returns matching known tags, and the component preview renders the UX multi-select field. | Pass |
| Custom tags require an existing or new category | The reusable form has an explicit new-tag row with required name/category validation; its category field autocompletes stored values and allows new text. Vacancy persistence is deferred to TICK-204. | Ready for TICK-204 |

The complete suite passed with 57 tests and 368 assertions. Overall line
coverage was 93.97%, exceeding the enforced 90% minimum. PHPStan, Deptrac,
PHP CS Fixer, Doctrine mapping/schema validation, Twig linting, and container
linting also passed.
