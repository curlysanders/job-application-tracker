# Job Application Tracker

[![codecov](https://codecov.io/github/curlysanders/job-application-tracker/graph/badge.svg?token=Z76SXCMIDK)](https://codecov.io/github/curlysanders/job-application-tracker)

**Job Application Tracker** is a self-hosted, privacy-first job application and vacancy management platform. Built using Symfony 8.1, PHP 8.5, and FrankenPHP in worker mode, it is optimized to run with a low memory footprint on home servers (such as a Synology NAS via Docker) or VPS instances.

It helps job seekers track vacancies, record application audit histories, manage contact networks, prep for interviews with scratchpad notes, and visually track their job hunt pipeline.

---

## 🚀 Key Features

### Phase 1 (Core Tracking Engine)
- **Interactive Chevron Pipeline Dashboard**: Visual chevron progress tracker with real-time status counts and instant filtering.
- **Vacancy Aggregate Management**: Manual entry for vacancy details, salary ranges, currency codes, work modes (Remote/Hybrid/Onsite), and structured summary text blocks.
- **Salary & Commute Threshold Warnings**: Visual alert indicators when vacancy salary ranges fall below user profile preferences.
- **Workflow State Machine & Audit History**: Event-driven state transitions (`BOOKMARKED` → `APPLYING` → `APPLIED` → `INTERVIEWING` → `NEGOTIATING` → `ACCEPTED`) with automatic transition audit logs and note taking.
- **Standalone Entities**: Decoupled Company and Recruiter entities supporting multiple contact points (Name, Email, Phone, LinkedIn).
- **Managed Tech Stack Tagging**: Standardized technology tags for exact filtering and future AI matching.
- **Flysystem Resume Storage**: Local file abstraction layer for single active resume management (PDF/DOCX).
- **Interview Scratchpad & Reminders**: Markdown rich text notes per vacancy and target `next_action_at` reminder dates.

### Future Roadmap
- **Phase 2 (Symfony AI)**: Automated vacancy text parsing, resume skills extraction, and AI-driven match scoring.
- **Phase 3 (Integrations & Cloud)**: Distance matrix commute calculation, OpenGraph URL scraping, Google OAuth SSO, and AWS S3 storage adapters.

---

## 🛠 Tech Stack & Architecture

- **Backend Framework**: Symfony 8.1 / PHP 8.5
- **Application Server**: FrankenPHP 1.12.7 (Worker Mode enabled)
- **Database**: MariaDB 12.3.3
- **Frontend / Assets**: Symfony UX 3.4 + AssetMapper (Zero Node.js build step required)
- **Styling & Interactivity**: Flowbite (Tailwind CSS) + Twig Components + Stimulus Controllers
- **File Storage**: Symfony Flysystem Bundle (`league/flysystem-bundle`)
- **Messaging & Event Queue**: Symfony Messenger (Outbox Pattern with Doctrine Transport)
- **State Machine**: Symfony Workflow Component

### Architecture Highlights
- **Pragmatic Clean Architecture**: Enforces strict hexagonal boundaries for external integrations (File Storage, Outbox Queue, AI Services) while maintaining a lightweight domain core using Doctrine ORM entities directly for standard CRUD operations.
- **CQRS Lite**: Clean separation between application commands (updates, status shifts, file uploads) and optimized repository read queries for dashboard rendering.
- **Audit Logging over ES**: Uses relational transition logs written via workflow listeners rather than full Event Sourcing.

---

## 📋 Prerequisites

- Docker & Docker Compose
- Docker host hardware with >= 1 GB available RAM (tested on Synology DS416play with 4GB RAM)

---

## ⚡️ Quick Start / Local Development

1. **Clone and configure**:
   ```bash
   git clone https://github.com/curlysanders/job-application-tracker.git
   cd job-application-tracker
   cp .env.local.example .env.local
   ```
   Set a unique `APP_SECRET` in `.env.local`. The checked-in database credentials
   are local-development defaults; an existing database volume needs its original
   credentials. MariaDB initialization variables apply only to a fresh volume.

2. **Build and install dependencies before starting workers**:
   ```bash
   docker compose build app
   docker compose run --rm --no-deps app composer install
   docker compose up -d
   ```
   Composer also restores the pinned importmap dependencies. No Node.js or CSS
   compiler is required.

3. **Validate the runtime and database**:
   ```bash
   docker compose exec app php bin/console about
   docker compose exec app php bin/console dbal:run-sql 'SELECT VERSION(), 1'
   ```
   Apply the committed application migrations before using a fresh database:
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Open the application** at `https://localhost/`. The component preview at
   `https://localhost/_components` is available only in `dev` and `test`.

The development Compose override automatically restarts workers after changes to
application code, configuration, templates, and frontend assets. After changing
dependencies or Compose settings, run `docker compose up -d --no-deps app`.
Local development defaults to HTTPS. FrankenPHP/Caddy automatically redirects
`http://localhost/` to HTTPS after the local CA is trusted.

### Local HTTPS and certificate trust

The development stack serves `https://localhost/` by default. FrankenPHP's
bundled Caddy instance creates a local certificate authority (CA) and an
automatically renewed certificate for `localhost`; it does not use a public CA,
DNS, or externally reachable ports.

1. Fresh installs receive this setting from `.env.local.example`. Existing
   checkouts should set Symfony's URI in the untracked `.env.local` to:
   ```dotenv
   DEFAULT_URI=https://localhost
   ```
2. Start the development stack normally:
   ```bash
   docker compose up -d
   ```
3. After the app is running, copy Caddy's root certificate from the container
   and add it to the trust store of the Docker host. Use the command for the
   host operating system:

   Linux (Debian/Ubuntu and other distributions using `update-ca-certificates`):
   ```bash
   docker compose cp app:/data/caddy/pki/authorities/local/root.crt /tmp/job-application-tracker-caddy-root.crt
   sudo install -m 0644 /tmp/job-application-tracker-caddy-root.crt /usr/local/share/ca-certificates/job-application-tracker-caddy-root.crt
   sudo update-ca-certificates
   ```

   macOS:
   ```bash
   docker compose cp app:/data/caddy/pki/authorities/local/root.crt /tmp/job-application-tracker-caddy-root.crt
   sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/job-application-tracker-caddy-root.crt
   ```

   Windows (PowerShell):
   ```powershell
   docker compose cp app:/data/caddy/pki/authorities/local/root.crt "$env:TEMP\job-application-tracker-caddy-root.crt"
   certutil -addstore -f "ROOT" "$env:TEMP\job-application-tracker-caddy-root.crt"
   ```

   Chrome normally uses the operating-system trust store; fully restart it after
   updating that store. If Chrome still shows a warning, open
   `chrome://settings/certificates`, select the **Authorities** tab, choose
   **Import**, select `/tmp/job-application-tracker-caddy-root.crt`, and enable
   **Trust this certificate for identifying websites**. Restart Chrome after the
   import. Firefox configurations that do not use the operating-system trust
   store similarly require importing that same `root.crt` under the browser's
   certificate-authority settings.

4. Open `https://localhost/` and confirm that the browser does not display a
   certificate warning. `curl -I https://localhost/` should also succeed after
   the root CA is trusted.

The root CA is retained in the `caddy_data` Docker volume. If that volume is
removed, Caddy creates a new CA on the next HTTPS startup and the trust step
must be repeated. Each additional device accessing the application also needs
this root CA installed; it is intentionally trusted only on devices you
administer.

### HTTP-only development

HTTPS is the default because it better matches production and lets browser
features that require a secure context work locally. To deliberately run plain
HTTP, set the project-facing FrankenPHP override before creating the app
container:

```bash
export FRANKENPHP_SERVER_NAME=:80
docker compose up -d --no-deps app
```

If Symfony needs to generate absolute URLs in that mode, also set
`DEFAULT_URI=http://localhost` in `.env.local`. To return to the HTTPS default,
unset `FRANKENPHP_SERVER_NAME`, restore the HTTPS URI, and recreate the app.
The older `SERVER_NAME` environment variable remains supported for existing
deployment setups, but `FRANKENPHP_SERVER_NAME` is preferred for new local
configuration.

For production, publish assets with `APP_ENV=prod APP_DEBUG=0 php bin/console
asset-map:compile` inside the deployment container after installing dependencies.
This copies/version-tags assets; it does not compile JavaScript or Tailwind CSS.
Do not retain `public/assets/` from a production build when working on development
assets, as published assets take precedence.

See [TICK-101 validation and benchmark instructions](docs/development/phase_1/tick-101-validation.md).

---

## 📖 Project Documentation

Documentation has been structured under the `docs/` folder:

- **[`docs/architecture.md`](./docs/architecture.md)** — Architectural blueprint, pragmatic hexagonal patterns, and CQRS Lite guidelines.
- **[`docs/workflow.md`](./docs/workflow.md)** — GitHub workflow and CI/CD foundation.
- **[`docs/development/roadmap.md`](./docs/development/roadmap.md)** — High-level phase vision and long-term features preview.
- **[`docs/development/phase_1/epics.md`](./docs/development/phase_1/epics.md)** — Phase 1 domain data model, aggregate entities, and epic definitions.
- **[`docs/development/phase_1/tickets.md`](./docs/development/phase_1/tickets.md)** — Sprints 1–4 developer execution plan with actionable tickets and acceptance criteria.

---

## 📄 License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

AGPL-3.0 ensures the project remains free and open-source for personal use while requiring anyone who hosts a modified version as a public or commercial cloud service (SaaS) to publish their source code changes under the same license.
