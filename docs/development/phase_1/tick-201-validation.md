# TICK-201 validation

## Implementation

- Companies and recruiters are standalone, system-wide domain entities with
  optional website metadata and (for companies) industry.
- Direct contacts are relational child entities. A database check constraint
  and domain invariant ensure each contact belongs to exactly one owner.
- Authenticated users manage companies at `/app/companies` and recruiters at
  `/app/recruiters`. Create and edit submissions dispatch application commands;
  controllers do not persist domain entities directly.
- The management lists support server-side search. Company search covers name,
  industry, and direct-contact name/email; recruiter search covers agency name
  and direct-contact name/email.

## Repeatable checks

Run from the repository root after starting Docker Compose and provisioning the
test databases as described in `tick-103-validation.md`:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app composer tests
docker compose exec app composer ci:phpstan
docker compose exec app composer ci:deptrac
docker compose exec app composer ci:php-cs-fixer
docker compose exec app php bin/console doctrine:schema:validate --skip-sync --env=dev
```

## Results — 2026-09-21

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| User can create, edit, and search standalone Companies and Recruiters | Functional coverage authenticates a user, exercises both management flows, and searches stored records by direct-contact fields. | Pass |
| Contact details support multiple entries per company/agency | Functional coverage creates a company with two contacts, then edits it successfully; persisted collection counts are asserted. | Pass |

The complete suite passed with 26 tests and 198 assertions. PHPStan, Deptrac,
PHP CS Fixer, Doctrine mapping validation, and the local development migration
also passed.
