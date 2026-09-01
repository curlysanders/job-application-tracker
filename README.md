# Job Application Tracker

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

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-username/job-application-tracker.git
   cd job-application-tracker
   ```

2. **Start Docker Containers**:
   ```bash
   docker compose up -d --build
   ```

3. **Install PHP Dependencies**:
   ```bash
   docker compose exec app composer install
   ```

4. **Run Database Migrations & Database Seeding**:
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
   ```

5. **Access the Application**:
   Open your browser and navigate to `http://localhost:8080` (or your configured Docker port).

---

## 📖 Project Documentation

Documentation has been structured under the `docs/` folder:

- **[`docs/architecture.md`](./docs/architecture.md)** — Architectural blueprint, pragmatic hexagonal patterns, and CQRS Lite guidelines.
- **[`docs/development/roadmap.md`](./docs/development/roadmap.md)** — High-level phase vision and long-term features preview.
- **[`docs/development/phase_1/epics.md`](./docs/development/phase_1/epics.md)** — Phase 1 domain data model, aggregate entities, and epic definitions.
- **[`docs/development/phase_1/tickets.md`](./docs/development/phase_1/tickets.md)** — Sprints 1–4 developer execution plan with actionable tickets and acceptance criteria.

---

## 📄 License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

AGPL-3.0 ensures the project remains free and open-source for personal use while requiring anyone who hosts a modified version as a public or commercial cloud service (SaaS) to publish their source code changes under the same license.
