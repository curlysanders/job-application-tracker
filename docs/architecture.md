# Job Application Tracker — Architecture Blueprint

## 1. Executive Summary & Vision

**Job Application Tracker** (working title for Phase 1 open-source release) is a self-hosted, privacy-first job application and vacancy management platform. Built to run efficiently on low-resource hardware like a home NAS (Synology DS416play, 4GB RAM) via Docker and FrankenPHP, it provides job seekers with structured tracking, salary alignment insights, application status history, and scratchpad prep notes.

Phase 1 establishes a rock-solid foundation, focused on manual data entry, responsive interactive pipeline visualization, robust entity modeling, and outbox messaging, laying the groundwork for future AI-driven resume matching and automatic vacancy parsing (Phase 2+).

---

## 2. Technical Stack & Infrastructure Architecture

### Core Stack
- **Language**: PHP 8.5 (leveraging latest performance & type system enhancements)
- **Framework**: Symfony 8.1
- **Application Server / Runtime**: FrankenPHP 1.12.7 (running in Worker Mode for high-performance zero-overhead execution)
- **Database**: MariaDB 12.3.3
- **Asset / Frontend System**: Symfony UX 3.4 + AssetMapper (no Node.js build step required)
- **UI Framework**: Flowbite (Tailwind CSS native components) + Twig Components
- **Interactivity**: Stimulus Controllers via Symfony UX

### Storage & Integration Boundaries
- **File Storage**: Symfony Flysystem Abstraction (`league/flysystem-bundle`). Local disk storage in Phase 1, seamlessly swappable to AWS S3 or MinIO in future cloud deployments.
- **Asynchronous Messaging**: Symfony Messenger configured with the **Outbox Pattern** (Doctrine transport for transactional event queuing + background worker execution).
- **Workflow / State Engine**: Symfony Workflow Component (configured as a strict state machine with transition validation and event listeners).

---

## 3. Architectural Blueprint & Pragmatic Guidelines

### Decoupling vs. Pragmatism
- **Pragmatic Clean Architecture**:
    - **Domain Core**: Business rules, status transitions, and salary/commute matching logic reside cleanly in the domain layer.
    - **Doctrine Integration**: To avoid excessive mapping boilerplate on a single-developer codebase, Doctrine ORM entities serve as domain models directly for internal CRUD operations.
    - **Strict Hexagonal Boundaries**: Enforced at external infrastructure interfaces—specifically File Storage (Flysystem adapter), Messaging (Messenger Outbox), and future AI integrations.
- **CQRS Lite**:
    - **Write Side**: Commands dispatched via application services / Symfony Messenger commands for state updates, resume uploads, and vacancy creation.
    - **Read Side**: Direct repository queries optimized with DTOs and database indexes powering interactive Twig Components and dashboard tables without multi-layered mapper overhead.
- **Audit Logging over Event Sourcing (ES)**:
    - Full Event Sourcing is explicitly rejected as unnecessary complexity.
    - State tracking uses a dedicated relational table (`vacancy_status_history`) populated automatically by Symfony Workflow listeners whenever a state transition occurs.

---

## 4. Domain Data Model & Key Entities

```
+------------------+         +--------------------+         +-----------------------+
|       User       | 1     * |      Vacancy       | *     * |       TechStack       |
|------------------|---------|--------------------|---------|-----------------------|
| - id             |         | - id               |         | - id                  |
| - email          |         | - title            |         | - name (e.g. PHP)     |
| - password       |         | - full_text        |         | - category (Backend)  |
| - min_salary     |         | - summary_blocks   |         +-----------------------+
| - max_commute    |         | - status           |
| - transport_mode |         | - min/max salary   |         +-----------------------+
| - resume_path    |         | - currency_code    | *     1 |        Company        |
+------------------+         | - work_mode        |---------|-----------------------|
                             | - next_action_at   |         | - id                  |
                             | - excitement (0-5) |         | - name, website       |
                             +--------------------+         | - direct_contacts     |
                                | 1                         +-----------------------+
                                |
                                | *                         +-----------------------+
                             +--------------------+ *     1 |       Recruiter       |
                             | StatusHistoryAudit |---------|-----------------------|
                             |--------------------|         | - id                  |
                             | - from_status      |         | - agency_name         |
                             | - to_status        |         | - direct_contacts     |
                             | - transition_notes |         +-----------------------+
                             | - transitioned_at  |
                             +--------------------+
```

### Detailed Domain Attribute Rules
1. **User Profile**:
    - `min_preferred_salary`: Gross monthly amount (e.g., 4500.00 EUR).
    - `max_commute_minutes`: Max target one-way travel time (e.g., 45 minutes).
    - `preferred_transport_mode`: Enum (`CAR`, `PUBLIC_TRANSPORT`, `BIKE`, `WALKING`).
    - `active_resume_filename`: Path/reference managed via Flysystem.

2. **Company & Recruiter Entities**:
    - **Standalone Entities**: Modeled as system-wide / user-referenced entities so multiple vacancies can link to the same target employer or recruiter contact.
    - **Direct Contacts**: Embedded object or child entity capturing `name`, `email`, `phone`, `linkedin_url`.

3. **Vacancy Aggregate**:
    - **Text Summaries**: Structured JSON/text fields for *Requirements*, *Job Responsibilities*, *Preferred Qualifications*, *About Job*, *About Company*, *Compensation & Benefits*.
    - **URLs**: Array of original source links.
    - **Salary & Currency**: `min_salary`, `max_salary`, `currency_code` (ISO 4217: EUR, USD, GBP, CHF).
    - **Workplace Mode**: Enum (`REMOTE`, `HYBRID`, `ONSITE`) + `hybrid_schedule_details` text string.
    - **Status Pipeline**: Enum/String managed via Symfony Workflow:
      `BOOKMARKED` → `APPLYING` → `APPLIED` → `INTERVIEWING` → `NEGOTIATING` → [`ACCEPTED` | `I_WITHDREW` | `NOT_SELECTED` | `NO_RESPONSE`].
    - **Terminal Reasons**: Reason code/text when entering `I_WITHDREW` or `NOT_SELECTED` (e.g., *Salary Mismatch*, *Ghosted*, *Role Closed*).
    - **Next Action & Scratchpad**: `next_action_at` (DateTime), `next_action_title` (String), `scratchpad_notes` (Markdown rich text for interview prep).

4. **Tech Stack Tags**:
    - Managed lookup entity (e.g. `PHP`, `Symfony`, `Docker`, `MariaDB`, `TailwindCSS`, `AWS`). Prevents spelling variations and enables exact filtering and future AI matching.
