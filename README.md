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
   There are no application migrations or fixtures to run yet.

4. **Open the application** at `http://localhost/`. The component preview at
   `http://localhost/_components` is available only in `dev` and `test`.

Workers retain PHP code and configuration in memory. After changing PHP,
configuration, dependencies, or adding Stimulus controllers, run `docker compose restart app`. For Compose
settings, use `docker compose up -d --no-deps app`. Local development defaults to
HTTP; set `SERVER_NAME` explicitly for a deployment hostname and TLS.

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
