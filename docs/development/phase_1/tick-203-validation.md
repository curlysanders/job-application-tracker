# TICK-203 validation

## Implementation

- `Vacancy` is a Doctrine aggregate owned by a `User`, optionally linked to a
  reusable `Company` and `Recruiter`, and tagged with managed `TechStack`
  entities.
- It persists every Phase 1 authoring/detail field, including structured text,
  source URLs, work details, dates, workflow metadata, and scratchpad content.
  Status is the `VacancyStatus` backed enum and defaults to `BOOKMARKED`.
  Workflow transitions remain TICK-301.
- Salary ranges use Brick Money through the `SalaryRange` value object. Amounts
  are stored as exact decimals with an ISO 4217 currency code, defaulting to
  EUR. Supplied amounts must be positive and ordered.
- `Version20260923120000` adds vacancy tables, relationship foreign keys,
  required indexes, and database checks for salary ordering and excitement.

## Results — 2026-09-23

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Migration executes cleanly without schema errors | A fresh `job_application_tracker_test99` database migrated through all six migrations; Doctrine mapping and schema validation passed. | Pass |
| Foreign keys, indexes, and constraints are configured | The migration defines the owner/contact/tag foreign keys, explicit status/user/date-added indexes, and salary/excitement checks; Doctrine schema validation passed. | Pass |

The complete suite passed with 70 tests and 457 assertions. PHPStan, Deptrac,
PHP CS Fixer, Doctrine mapping/schema validation, Twig linting, and container
linting also passed. FrankenPHP was restarted after the PHP changes.
