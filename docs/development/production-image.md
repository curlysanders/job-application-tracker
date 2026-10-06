# Production image contract

The public repository builds the application image only. It never contains
production Compose files, deployment credentials, domain names, NAS paths, or
unencrypted secrets.

Release images are published to GHCR from signed version tags. A separate,
private deployment repository pins an image digest and contains the production
Compose stack, DSM reverse-proxy contract, SOPS-encrypted environment values,
backup tooling, and the Synology deployment runbook.

The production Dockerfile pins FrankenPHP 1.13.0 on Debian Trixie for both build and runtime stages. Updating FrankenPHP is an explicit reviewed change rather than an incidental rebuild result.

After GHCR has accepted a tagged image, the publishing workflow sends its
immutable digest to the private deployment repository through a
`repository_dispatch` event. It requires the public-repository Actions secret
`DEPLOYMENT_DISPATCH_TOKEN`: a fine-grained token limited to the private
deployment repository with **Contents: write** permission. The private
repository records the digest in its versioned release manifest; a NAS-local
scheduled task then applies that manifest. The public repository never receives
NAS access, deployment secrets, or the SOPS age key.

The image compiles AssetMapper assets during its build but deliberately discards
the build-time Symfony cache: production secrets are unavailable then. The
private deployment stack warms each runtime container's cache after SOPS has
provided the real environment and before it starts FrankenPHP or the worker.

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

When a trusted reverse proxy terminates HTTPS, set `SYMFONY_TRUSTED_PROXIES` to
the immediate proxy address. The Synology stack uses `REMOTE_ADDR` because its
application port is bound to the NAS loopback interface, so external clients
cannot connect to Caddy directly.
