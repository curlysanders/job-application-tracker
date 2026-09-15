# TICK-101 validation

## Implementation

- Symfony's native runtime runs `public/index.php` with two FrankenPHP workers.
- `GET /` (`app_home`) renders the shared header, responsive sidebar, and content.
- `GET /_components` (`app_component_preview`) is registered only in dev/test.
- Flowbite 3.1.2 uses importmap-managed JavaScript and locally vendored precompiled
  CSS. Custom styles are plain CSS. Flowbite initializes once on window load;
  Stimulus supplies modal focus trapping/restoration and dropdown Escape/ARIA
  state handling. No frontend compiler or
  runtime CDN is used.
- Anonymous Twig `FlashMessage` renders escaped messages, with unknown types
  falling back to info. Messages are consumed once and can be dismissed.
- `APP_REQUEST_TIMING=1` enables an `app` Server-Timing metric and JSON records in
  `var/log/performance.log`. Default is off. Measurement covers early
  `kernel.request` through late `kernel.response`, excluding cold worker startup,
  response transfer, termination, assets, and browser rendering. Timing state is
  request-local, and subrequests are ignored.

## Repeatable local benchmark

Start the regular development environment first. Use an isolated app container
with a temporary asset directory so production publication does not shadow dev
assets. Run from the repository root:

```bash
mkdir -p /tmp/tick101-assets
docker compose run --rm -d --no-deps --name tick101-benchmark \
  -p 127.0.0.1:18080:80 \
  -v /tmp/tick101-assets:/app/public/assets \
  -e SERVER_NAME=:80 \
  -e APP_ENV=prod -e APP_DEBUG=0 -e XDEBUG_MODE=off \
  -e APP_REQUEST_TIMING=1 \
  app sh -c 'php bin/console cache:warmup && php bin/console asset-map:compile && exec frankenphp run --config /etc/frankenphp/Caddyfile'
docker logs tick101-benchmark
```

Wait for `serving initial configuration` in the startup log, then:

```bash
php scripts/benchmark.php http://127.0.0.1:18080/
```

The script warms 20 requests and measures 100 sequential HTTP 200 responses.
It reads server execution time from `Server-Timing`, reports median, nearest-rank
p95 (95th ordered sample), and maximum, and fails if p95 is **30ms or higher**.
Failed responses, redirects, and absent/invalid timing headers also fail.
A fast result alone does not establish worker mode: inspect the active config:

```bash
docker exec tick101-benchmark php -r 'echo file_get_contents("http://localhost:2019/config/apps/frankenphp");'
```

It must show `/app/public/index.php` with two workers. The admin port remains
internal to the container. After validation:

```bash
docker stop tick101-benchmark
```

The temporary CSS output remains in `/tmp/tick101-assets` and can be removed.
The development app remains in dev mode, with timing disabled.

## Results — 2026-09-15

| Check | Result |
| --- | --- |
| Symfony / UX | 8.1.6 / Stimulus and Twig Components 3.4.0 |
| Container runtime | FrankenPHP 1.12.7, PHP 8.5.9, Caddy 2.11.4 |
| Active workers | Two, using `/app/public/index.php` |
| Fresh MariaDB | 12.3.3; Doctrine `SELECT VERSION(), 1` succeeds |
| Warm request median / p95 / maximum | 1.326ms / **1.995ms** / 2.858ms |
| Timing logs | 120 records, including 20 warmup requests |
| Automated tests | 6 tests, 43 assertions through ParaTest |
| Static checks | PHPStan, Deptrac, PHP CS Fixer, Twig and container lint pass |
| Browser checks | Chromium at 390px and 1440px; navigation, dropdown, modal, flash dismissal, keyboard focus, local assets, and no horizontal overflow pass |

The timing run used production mode, debug off, Xdebug off, warmed cache, and
published assets, with sequential requests from inside the app container. It
measures the foundation home page, not future database-backed dashboard work.

## Checks for future layout changes

```bash
composer ci:phpunit
composer ci:phpstan
composer ci:deptrac
composer ci:php-cs-fixer
php bin/console lint:twig templates
php bin/console lint:container
php bin/console debug:asset-map
```

At mobile and desktop widths, open `/_components`, toggle the mobile menu, open
the dropdown with keyboard and mouse and close it with Escape, open the modal, cycle Tab inside
it, close with Escape and the close button, and verify focus returns to its
trigger. Dismiss each flash type, navigate Home, and confirm consumed flashes do
not return. Check for console errors, failed/external asset requests, and overflow.
Production must return 404 for `/_components`.
