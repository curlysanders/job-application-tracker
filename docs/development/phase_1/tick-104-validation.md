# TICK-104 validation

## Implementation

- The existing private Flysystem `default.storage` stores resume objects under a
  server-generated `resumes/{user-id}/` path beneath `var/storage/default`,
  outside the public document root.
- `User` records the one active resume's storage path, original filename,
  detected MIME type, and upload timestamp. The synchronous `UploadResume`
  command writes the new object, persists its reference, and only then removes
  the old object. A cleanup failure cannot invalidate the active reference.
- The profile page has a separate multipart upload form. It requires PDF or
  DOCX content and limits uploads to 5 MB. The authenticated download endpoint
  streams only the current user's active resume as an attachment.

## Repeatable checks

Run from the repository root after starting Docker Compose. Provision and
migrate every ParaTest database when using a fresh local database volume:

```bash
docker compose exec app sh -lc 'php bin/console doctrine:database:create --if-not-exists --env=test && php bin/console doctrine:migrations:migrate --no-interaction --env=test && for token in $(seq 1 "$(nproc)"); do TEST_TOKEN="$token" php bin/console doctrine:database:create --if-not-exists --env=test && TEST_TOKEN="$token" php bin/console doctrine:migrations:migrate --no-interaction --env=test; done'
docker compose exec app composer tests
docker compose exec app composer ci:phpstan
docker compose exec app composer ci:deptrac
docker compose exec app composer ci:php-cs-fixer
docker compose exec app php bin/console lint:container --env=test
docker compose exec app php bin/console doctrine:schema:validate --skip-sync --env=dev
```

The development Compose override watches application code, configuration,
templates, and assets, so these changes reload FrankenPHP workers automatically.
Run `docker compose up -d --no-deps app` only after dependency or Compose changes.

## Results — 2026-09-21

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| Single active PDF/DOCX resume | Functional tests submit valid PDF and DOCX files through the profile form, persist their metadata, and verify the private Flysystem object. The form limits both MIME types to 5 MB. | Pass |
| Safe replacement | Functional test uploads a second resume, verifies the active reference changes, and verifies the old object is removed. | Pass |
| Private Flysystem storage | Functional test verifies a server-generated storage path and authenticated download; anonymous download redirects to login. | Pass |

The full suite passed with 23 tests and 163 assertions. PHPStan, Deptrac,
PHP CS Fixer, container lint, and Doctrine mapping validation passed.
