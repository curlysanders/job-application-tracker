# TICK-204 Validation

## Reproduction

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app composer ci:coverage
docker compose exec app composer analyse
docker compose exec app composer ci:php-cs-fixer
docker compose exec app php bin/console doctrine:schema:validate
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console lint:container
```

The vacancy create and edit form divides authoring into role/employer, summary,
compensation/work mode, and technology/excitement sections. It saves every
form field through the vacancy command handler, including custom technologies
with their required category. The client-side salary callout compares the
vacancy range with the user's preferred gross monthly salary using the selected
currency and cached ECB reference rates when available.

## Results — 2026-09-25

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Form successfully persists complete vacancy details | `VacancyAuthoringTest` creates a complete vacancy, verifies its stored fields and custom technology, then edits it successfully. | Pass |
| Visual highlight alerts the user below their profile threshold | The authoring controller provides profile salary and exchange-rate data to the Stimulus controller; the rendered warning callout uses the highlighted `salary-guidance-warning` style when the range is below the threshold. | Pass |

The complete suite passed with 74 tests and 511 assertions. Overall line
coverage was 94.42%, exceeding the enforced 90% minimum. PHPStan, Deptrac,
PHP CS Fixer, Doctrine mapping/schema validation, Twig linting, and container
linting passed.
