# TICK-404 validation

## Public application repository

The public repository contains only the production image contract:

- `.docker/php/Dockerfile.prod` installs production Composer dependencies and
  publishes AssetMapper assets; it discards its build-time cache because the
  deployment-only `APP_SECRET` is unavailable during image creation;
- `.dockerignore` excludes local environment files, dependencies, generated
  assets, caches, and Git metadata from the build context;
- the tagged-release GitHub workflow publishes the image to GHCR; and
- `/healthz` verifies that Symfony can query the database without exposing
  application or database details.

The deployment Compose stack, hostname, Synology paths, registry credential,
SOPS-encrypted values, and age private key are deliberately absent.

## Private deployment repository

`job-application-tracker-deploy` is a separate private Git repository. It
contains the production Compose stack, DSM reverse-proxy contract, SOPS
templates, immutable release manifest template, database backup/migration/
rollback scripts, and the Synology operations runbook. Its `secrets.prod.env` is intended to be an
encrypted SOPS file committed to that private repository; the age private key
is NAS-local and excluded from Git. The app and worker warm their independent
Symfony caches when their containers start, after SOPS has supplied the real
runtime environment.

On the Synology, DSM owns host ports 80 and 443. The app binds HTTP only to
`127.0.0.1:18080`; DSM Reverse Proxy terminates HTTPS and forwards requests to
that loopback endpoint. Symfony trusts the immediate sender only through
`SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR`.

## Automated validation

```bash
docker compose exec -T app composer tests -- tests/Functional/HealthControllerTest.php
docker compose exec -T app composer analyse
docker build --file .docker/php/Dockerfile.prod --tag job-application-tracker-production:test .
docker run --rm --entrypoint sh job-application-tracker-production:test -ec \
  'php -m | grep -qx pdo_mysql && test ! -e vendor/bin/phpunit && test -f public/assets/manifest.json && test ! -e /usr/local/etc/php/conf.d/99-xdebug.ini'
```

Render the private deployment stack using example values and validate the Caddy
configuration with the production image:

```bash
docker compose --env-file release.env.example --env-file secrets.prod.env.example \
  -f compose.prod.yaml config
docker run --rm --user 1000:1000 \
  -v "$PWD/Caddyfile:/etc/frankenphp/Caddyfile:ro" \
  --entrypoint frankenphp job-application-tracker-production:test \
  validate --config /etc/frankenphp/Caddyfile
```

## Synology deployment gate

Before the first public release, confirm the NAS image architecture and
Container Manager compatibility, reserve the NAS LAN address, determine whether
WAN ports 80/443 can reach DSM, configure the public DNS record and DSM
certificate/Reverse Proxy rule, provision an age key and least-privilege
GHCR/read-only deployment credentials, and configure encrypted external backups.

Run a restoration drill into an isolated stack and measure application and
worker memory during vacancy updates and resume validation before marking the
remaining acceptance criterion complete.
