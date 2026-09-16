# TICK-102 validation

## Implementation

- The `User` Doctrine entity stores a normalized email address, password hash,
  roles, and creation time in the `users` table. The email column has a unique
  database constraint.
- Registration at `/register` validates an email address and a confirmed
  password with at least 12 characters, uppercase and lowercase letters, a
  base-10 digit, and a symbol. Successful registration dispatches the
  synchronous `RegisterUser` command, creates a session, and redirects to
  `/app`.
- Symfony Security uses the Doctrine user provider, automatic password hashing,
  CSRF-protected login and logout, and login throttling. `/app` is restricted
  to `ROLE_USER` through access control.

## Repeatable checks

Run from the repository root after starting Docker Compose:

```bash
docker compose exec app composer tests
docker compose exec app php bin/console doctrine:schema:validate --skip-sync --env=dev
docker compose exec app php bin/console lint:container --env=test
```

The test environment uses the local MariaDB root connection so ParaTest can
access its per-worker `job_application_tracker_test*` databases.

## Results — 2026-09-16

| Acceptance criterion | Evidence | Result |
| --- | --- | --- |
| User can register with email and password | Functional test submits `/register`, verifies the normalized persisted user, automatic authentication, and the dashboard response. | Pass |
| Password hashes use modern password strength defaults | Security uses Symfony's `auto` password hasher; the functional test verifies the stored value differs from the submitted password and validates it through the hasher. | Pass |
| Unauthenticated users are redirected to login for dashboard routes | Functional test requests `/app` anonymously and verifies the redirect to `/login`. | Pass |

The acceptance suite completed with 12 tests and 84 assertions. Doctrine mapping
and the Symfony service container also validated successfully.
