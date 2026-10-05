# Production image contract

The public repository builds the application image only. It never contains
production Compose files, deployment credentials, domain names, NAS paths, or
unencrypted secrets.

Release images are published to GHCR from signed version tags. A separate,
private deployment repository pins an image digest and contains the production
Compose stack, Caddy policy, SOPS-encrypted environment values, backup tooling,
and the Synology deployment runbook.

The production runtime requires these environment variables:

- `APP_ENV=prod` and `APP_DEBUG=0`;
- `APP_SECRET`, supplied by the deployment secret store;
- `DATABASE_URL`, pointing at the internal MariaDB service;
- `DEFAULT_URI`, set to the canonical public HTTPS URL;
- `APP_STORAGE_DIR`, an absolute path to the mounted persistent Flysystem
  storage directory.

`/healthz` returns `200 {"status":"ok"}` only when Symfony can query the
database. It contains no application or database details and is intended for
container and deployment readiness checks.
