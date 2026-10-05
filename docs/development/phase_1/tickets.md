# Sprint 1: Foundation, User Profile & Storage Abstraction

## TICK-101: Environment Bootstrap & Base Layout Engine
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: None
- **Scope**:
    - Validate Symfony 8.1 running on FrankenPHP (PHP 8.5) and MariaDB docker container.
    - Configure Symfony UX 3.4, AssetMapper, and Flowbite (Tailwind CSS) styling framework.
    - Create base Twig layout (`base.html.twig`) with responsive navigation header, sidebar container, and flash notification messages.
- **Acceptance Criteria**:
    - [x] App renders cleanly via FrankenPHP worker mode with zero build-step overhead (AssetMapper).
    - [x] Flowbite CSS and JS components (modals, dropdowns, navigation) function correctly.
    - [x] Page load execution time logged and verified under 30ms locally (warm server p95; debug and Xdebug disabled).
- **Validation**: [Results and reproduction steps](tick-101-validation.md). Existing local database credentials require reconciliation; fresh-volume bootstrap and Doctrine connectivity passed.

---

## TICK-102: User Authentication & Security Scaffold
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: TICK-101
- **Scope**:
    - Implement `User` Doctrine entity (`id`, `email`, `password`, `roles`, `createdAt`).
    - Configure Symfony Security bundle with login form, password hasher, and logout handlers.
    - Build registration page and protected application route guards (`/app/*`).
- **Acceptance Criteria**:
    - [x] User can register with email and password.
    - [x] Password hashes use modern password strength defaults.
    - [x] Unauthenticated users are redirected to login when accessing dashboard routes.
- **Validation**: [Results and reproduction steps](tick-102-validation.md).

---

## TICK-103: User Preference Profile & Salary/Commute Settings
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: TICK-102
- **Scope**:
    - Extend `User` entity with `min_preferred_salary` (decimal), `max_commute_minutes` (integer), and `preferred_transport_mode` (string enum: Car, Public Transport, Bike, Walking).
    - Create Profile Settings form under `/app/profile`.
    - Add domain logic method `User::evaluatesSalary(float $grossSalary): SalaryFitStatus` returning `MEETS_TARGET`, `BELOW_TARGET`.
- **Acceptance Criteria**:
    - [x] User can update salary preference, commute budget, and transport mode in profile settings.
    - [x] Form validates positive numeric inputs for monetary amounts and commute times.
- **Validation**: [Results and reproduction steps](tick-103-validation.md).

---

## TICK-104: Flysystem Resume File Storage Service
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: TICK-102
- **Scope**:
    - Install and configure `league/flysystem-bundle` using local adapter storage.
    - Build `ResumeUploaderService` abstraction interface (`uploadResume(User $user, UploadedFile $file)`).
    - Implement PDF and DOCX mime-type and size validation (max 5MB).
    - Add resume upload/view section on User Profile page.
- **Acceptance Criteria**:
    - [x] User can upload a single active resume (PDF or DOCX).
    - [x] Uploading a new resume safely overwrites/replaces the previous file reference.
    - [x] Files are stored outside the public document root via Flysystem abstraction.
- **Validation**: [Results and reproduction steps](tick-104-validation.md).

---

# Sprint 2: Core Domain Models & Manual Vacancy Entry

## TICK-201: Standalone Company & Recruiter Domain Entities
- **Epic**: Epic 2 (Domain Data & Forms)
- **Dependencies**: TICK-102
- **Scope**:
    - Create user-owned `Company` entity (`name`, `website`, `industry`, `direct_contacts` array/embeddable: name, email, phone, linkedin).
    - Create user-owned `Recruiter` entity (`agency_name`, `website`, `direct_contacts` array/embeddable: name, email, phone, linkedin).
    - Create CRUD controllers and forms for standalone Company and Recruiter management.
- **Acceptance Criteria**:
    - [x] User can create, edit, and search standalone Companies and Recruiters.
    - [x] Contact details support multiple contact entries per company/agency.
    - [x] Users can only list, select, and modify their own Companies and Recruiters.
- **Validation**: [Results and reproduction steps](tick-201-validation.md).

---

## TICK-202: TechStack Tag Managed Entity & Form Component
- **Epic**: Epic 2 (Domain Data & Forms)
- **Dependencies**: TICK-101
- **Scope**:
    - Create `TechStack` entity (`id`, `name`, `slug`, `category`).
    - Add database seed script for standard technologies (PHP, Symfony, MariaDB, Docker, React, Python, AWS, etc.).
    - Build UX autocomplete select component for attaching multiple tech stack items to entities.
- **Acceptance Criteria**:
    - [x] Pre-seeded tech tags are selectable via standard UI multi-select or autocomplete tags.
    - [ ] Custom tech tags can be added on the fly during vacancy entry (delivered by TICK-204 using this component).
- **Validation**: [Results and reproduction steps](tick-202-validation.md).

---

## TICK-203: Vacancy Aggregate & Database Schema Migration
- **Epic**: Epic 2 (Domain Data & Forms)
- **Dependencies**: TICK-103, TICK-201, TICK-202
- **Scope**:
    - Create `Vacancy` aggregate entity with all Phase 1 properties:
        - Text blocks: full text, requirements, responsibilities, preferred qualifications, about job, about company, compensation benefits.
        - Metadata: title, source URLs (json array), how_to_apply, location, min/max salary, currency code (EUR default), work mode (Remote/Hybrid/Onsite), hybrid details.
        - Dates: date_added, date_posted, deadline, date_applied, next_action_at.
        - Workflow & Meta: status, archived, excitement (0-5 stars), contract_type, application_source, terminal_reason, scratchpad_notes.
    - Establish relationships: `User` (ManyToOne), `Company` (ManyToOne, nullable), `Recruiter` (ManyToOne, nullable), `TechStack` (ManyToMany).
- **Acceptance Criteria**:
    - [x] Migration executes cleanly without schema errors.
    - [x] Foreign keys, indexes (status, user_id, date_added), and constraints are correctly configured.
- **Validation**: [Results and reproduction steps](tick-203-validation.md).

---

## TICK-204: Multi-Section Vacancy Authoring UI & Validation
- **Epic**: Epic 2 (Domain Data & Forms)
- **Dependencies**: TICK-203
- **Scope**:
    - Build manual Vacancy Creation and Edit form divided into logical UI sections:
        1. Role & Employer Info (Title, Company select, Recruiter select, Location, Source).
        2. Summary Text Blocks (Paste area for Requirements, Responsibilities, About Job, etc.).
        3. Compensation & Work Mode (Min/Max Salary, Currency, Work Mode, Hybrid schedule).
        4. Tech Stack & Excitement Rating (Tag selection, 5-star rating widget).
    - Display dynamic threshold warnings if provided salary is below `User::min_preferred_salary`.
- **Acceptance Criteria**:
    - [x] Form successfully persists complete vacancy details.
    - [x] Visual highlight badge alerts the user if vacancy salary falls below their profile threshold.
- **Validation**: [Results and reproduction steps](tick-204-validation.md).

---

# Sprint 3: Status Workflow, Pipeline Dashboard & Scratchpad

## TICK-301: Symfony Workflow & Status Machine Definition
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-203
- **Scope**:
    - Configure `vacancy_status` state machine in `config/packages/workflow.php`:
        - States: `BOOKMARKED`, `APPLYING`, `APPLIED`, `INTERVIEWING`, `NEGOTIATING`, `ACCEPTED`, `I_WITHDREW`, `NOT_SELECTED`, `NO_RESPONSE`.
        - Allowed transitions: defined logically (e.g., `BOOKMARKED` -> `APPLYING` / `APPLIED` / `I_WITHDREW`).
    - Enforce valid transitions through the state machine definition.
- **Acceptance Criteria**:
    - [x] Workflow state machine prevents illegal status jumps (e.g., direct jump from `BOOKMARKED` to `ACCEPTED`).
    - [x] State transitions can be triggered programmatically and via UI buttons.
- **Validation**: [Results and reproduction steps](tick-301-validation.md).

---

## TICK-302: Audit History & Transition Event Logging
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-301
- **Scope**:
    - Create `VacancyStatusHistory` entity (`id`, `vacancy_id`, `from_status`, `to_status`, `notes`, `transitioned_at`).
    - Create Symfony Workflow event listener (`workflow.vacancy_status.transition`) capturing transition events and creating audit records.
    - Add optional modal prompt when changing status to capture notes (e.g., "First screening scheduled").
- **Acceptance Criteria**:
    - [x] Every status change writes an audit record with timestamp and target status.
    - [x] Transition timeline displays cleanly on the vacancy edit page.
- **Validation**: [Results and reproduction steps](tick-302-validation.md).

---

## TICK-303: Interactive Chevron Pipeline Component (Symfony UX Twig Component)
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-301
- **Scope**:
    - Build reusable Twig Component `VacancyChevronPipeline.html.twig`.
    - Render chevron blocks corresponding to main pipeline steps: `BOOKMARKED` -> `APPLYING` -> `APPLIED` -> `INTERVIEWING` -> `NEGOTIATING` -> `ACCEPTED`.
    - Each chevron step displays the total count of active vacancies in that status.
    - Clicking a chevron step filters the vacancy table below by that selected status.
- **Acceptance Criteria**:
    - [x] Chevron visual matches requested pipeline UI design (active highlighting, clean borders, responsive wrapping).
    - [x] Clicking a chevron filters vacancies dynamically via Stimulus / Turbo frame without full page reload.
- **Validation**: [Results and reproduction steps](tick-303-validation.md).

---

## TICK-304: Vacancy Overview Table & Advanced Search/Filter View
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-303
- **Scope**:
    - Build primary Dashboard Overview view (`/app`).
    - Implement search bar (by Title, Company Name, Tech Stack).
    - Add filter controls: Status, Excitement Rating (0-5 stars), Work Mode, Salary Fit.
    - Table columns: Job Title, Company/Recruiter, Work Mode, Tech Stack Tags, Excitement, Salary, Next Action, Current Status pill.
- **Acceptance Criteria**:
    - [x] Table renders fast with server-side pagination (20 items per page).
    - [x] Quick actions menu per row: View details, Change status dropdown, Archive, Delete.
- **Validation**: [Results and reproduction steps](tick-304-validation.md).

---

## TICK-305: Vacancy Detail View with Scratchpad & Next Action Reminders
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-302, TICK-304
- **Scope**:
    - Build detailed view (`/app/vacancies/{id}`).
    - Render organized vacancy text summary blocks.
    - Integrate Markdown scratchpad editor for prep notes, key contacts, and questions to ask during interview.
    - Add "Next Action" reminder widget (`next_action_title`, `next_action_at`).
- **Acceptance Criteria**:
    - [x] Scratchpad notes save asynchronously upon user edit/blur.
    - [x] Vacancies with upcoming or overdue `next_action_at` display alert callouts on the dashboard.
- **Validation**: [Results and reproduction steps](tick-305-validation.md).

---

# Sprint 4: Outbox Messaging, Async Workers & Optimization

## TICK-401: Symfony Messenger & Doctrine Outbox Infrastructure
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: TICK-302
- **Scope**:
    - Persist named domain facts through Lingoda Domain Events and its transactional Doctrine outbox.
    - Record aggregate creation, updates, workflow transitions, archive/restore, resume replacement, and deletion with the resulting changed-property values.
    - Run the `outbox` Messenger consumer as a dedicated Compose worker.
    - Convert aggregate roots and their relations to binary UUIDv7 identifiers through a data-preserving migration.
- **Acceptance Criteria**:
    - [x] Named domain events are stored transactionally even when no handler exists.
    - [x] An `outbox` worker transport is configured for reliable at-least-once delivery.
    - [x] Changed-property payloads contain resulting values and omit secrets.
- **Validation**: [Results and reproduction steps](tick-401-validation.md).

---

## TICK-402: Async Status Logging & Storage Notification Workers
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: TICK-401
- **Scope**:
    - Refactor status history writing logic to execute asynchronously via Messenger Outbox handler.
    - Add async file validation listener for newly uploaded resume files.
- **Acceptance Criteria**:
    - [x] HTTP responses for status updates complete in under 15ms.
    - [x] Audit logs and file post-processing tasks complete asynchronously in background.
- **Validation**: [Results and reproduction steps](tick-402-validation.md).

---

## TICK-403: Responsive Polish & Query Index Tuning
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: All prior tickets
- **Scope**:
    - Test dashboard UI on mobile, tablet, and desktop breakpoints using Flowbite responsive utilities.
    - Optimize MariaDB indexes on `user_id`, `status`, `next_action_at`, and `date_added`.
    - Defer Synology DS416play FrankenPHP memory-footprint validation until deployment preparation.
- **Acceptance Criteria**:
    - [x] Dashboard pipeline and vacancy cards are fully responsive on mobile viewports, with no page-level horizontal overflow at supported phone widths.
    - [ ] Validate container memory usage under peak execution during deployment preparation.
- **Validation**: [Results and reproduction steps](tick-403-validation.md).

---

## TICK-404: Synology Production Deployment Preparation
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: TICK-401, TICK-402, TICK-403
- **Scope**:
    - Publish a minimal immutable FrankenPHP production image to GHCR from release tags.
    - Provide a private, Git-backed Synology deployment stack with SOPS-encrypted secrets, DSM Reverse Proxy HTTPS, internal Caddy HTTP, MariaDB, persistent resume storage, the outbox worker, backup, migration, rollback, and health-check procedures.
    - Keep deployment configuration and secrets outside this public application repository.
- **Acceptance Criteria**:
    - [x] The public repository builds a production image without Xdebug, Git, development dependencies, or bind-mounted source.
    - [x] The application exposes a public database-readiness endpoint and production storage can use a mounted absolute directory.
    - [x] The private deployment stack pins GHCR images by digest and keeps encrypted production secrets versioned separately from private decryption keys.
    - [ ] Validate public HTTPS, automatic certificate renewal, backup restoration, worker delivery, and peak memory use on the Synology.
- **Validation**: [Results and reproduction steps](tick-404-validation.md).
