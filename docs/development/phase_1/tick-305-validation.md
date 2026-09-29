# TICK-305 Validation

## Reproduction

Run the checks from the application container:

```bash
docker compose exec -T app composer ci:coverage
docker compose exec -T app composer analyse
docker compose exec -T app composer ci:php-cs-fixer
docker compose exec -T app php bin/console doctrine:schema:validate
docker compose exec -T app php bin/console lint:twig templates
docker compose exec -T app php bin/console lint:container
```

Sign in and open a dashboard vacancy through its title or **View details**.
The detail page renders the structured vacancy information, contacts, sources,
status history, and an edit link. Use the toolbar for headings, bold, italic,
lists, links, and inline code; type Markdown directly when needed. Wait briefly
or move focus away, and confirm the preview and **Saved** status update without
a page reload. Create, update, and clear a next action; a date and time is
required whenever a title is supplied. Dashboard callouts include unarchived
vacancies due today or earlier, but omit future and other users' vacancies.

## Results — 2026-09-29

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Scratchpad notes save asynchronously upon user edit/blur | `VacancyDetailTest` covers the owner-scoped JSON endpoint, CSRF rejection, persistence, and safe Markdown rendering. The Stimulus controller debounces input and saves on blur. | Pass |
| Vacancies with due `next_action_at` display dashboard alert callouts | `VacancyReminderDashboardTest` covers due-today and overdue reminders plus exclusion of future, archived, and other users' vacancies. | Pass |

The full suite passed with 98 tests and 865 assertions. Line coverage was
93.41%, exceeding the enforced 90% minimum. PHPStan, Deptrac, PHP CS Fixer,
Doctrine schema validation, Twig linting, and container linting passed.

No migration was required: `scratchpad_notes`, `next_action_title`, and
`next_action_at` already existed in the vacancy schema.
