# Sprint 1: Foundation, User Profile & Storage Abstraction

## TICK-101: Environment Bootstrap & Base Layout Engine
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: None
- **Scope**:
    - Validate Symfony 8.1 running on FrankenPHP (PHP 8.5) and MariaDB docker container.
    - Configure Symfony UX 3.4, AssetMapper, and Flowbite (Tailwind CSS) styling framework.
    - Create base Twig layout (`base.html.twig`) with responsive navigation header, sidebar container, and flash notification messages.
- **Acceptance Criteria**:
    - [ ] App renders cleanly via FrankenPHP worker mode with zero build-step overhead (AssetMapper).
    - [ ] Flowbite CSS and JS components (modals, dropdowns, navigation) function correctly.
    - [ ] Page load execution time logged and verified under 30ms locally.

---

## TICK-102: User Authentication & Security Scaffold
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: TICK-101
- **Scope**:
    - Implement `User` Doctrine entity (`id`, `email`, `password`, `roles`, `createdAt`).
    - Configure Symfony Security bundle with login form, password hasher, and logout handlers.
    - Build registration page and protected application route guards (`/app/*`).
- **Acceptance Criteria**:
    - [ ] User can register with email and password.
    - [ ] Password hashes use modern password strength defaults.
    - [ ] Unauthenticated users are redirected to login when accessing dashboard routes.

---

## TICK-103: User Preference Profile & Salary/Commute Settings
- **Epic**: Epic 1 (Scaffold & Auth)
- **Dependencies**: TICK-102
- **Scope**:
    - Extend `User` entity with `min_preferred_salary` (decimal), `max_commute_minutes` (integer), and `preferred_transport_mode` (string enum: Car, Public Transport, Bike, Walking).
    - Create Profile Settings form under `/app/profile`.
    - Add domain logic method `User::evaluatesSalary(float $grossSalary): SalaryFitStatus` returning `MEETS_TARGET`, `BELOW_TARGET`.
- **Acceptance Criteria**:
    - [ ] User can update salary preference, commute budget, and transport mode in profile settings.
    - [ ] Form validates positive numeric inputs for monetary amounts and commute times.

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
    - [ ] User can upload a single active resume (PDF or DOCX).
    - [ ] Uploading a new resume safely overwrites/replaces the previous file reference.
    - [ ] Files are stored outside the public document root via Flysystem abstraction.

---

# Sprint 2: Core Domain Models & Manual Vacancy Entry

## TICK-201: Standalone Company & Recruiter Domain Entities
- **Epic**: Epic 2 (Domain Data & Forms)
- **Dependencies**: TICK-102
- **Scope**:
    - Create `Company` entity (`name`, `website`, `industry`, `direct_contacts` array/embeddable: name, email, phone, linkedin).
    - Create `Recruiter` entity (`agency_name`, `website`, `direct_contacts` array/embeddable: name, email, phone, linkedin).
    - Create CRUD controllers and forms for standalone Company and Recruiter management.
- **Acceptance Criteria**:
    - [ ] User can create, edit, and search standalone Companies and Recruiters.
    - [ ] Contact details support multiple contact entries per company/agency.

---

## TICK-202: TechStack Tag Managed Entity & Form Component
- **Epic**: Epic 2 (Domain Data & Forms)
- **Dependencies**: TICK-101
- **Scope**:
    - Create `TechStack` entity (`id`, `name`, `slug`, `category`).
    - Add database seed script for standard technologies (PHP, Symfony, MariaDB, Docker, React, Python, AWS, etc.).
    - Build UX autocomplete select component for attaching multiple tech stack items to entities.
- **Acceptance Criteria**:
    - [ ] Pre-seeded tech tags are selectable via standard UI multi-select or autocomplete tags.
    - [ ] Custom tech tags can be added on the fly during vacancy entry.

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
    - [ ] Migration executes cleanly without schema errors.
    - [ ] Foreign keys, indexes (status, user_id, date_added), and constraints are correctly configured.

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
    - [ ] Form successfully persists complete vacancy details.
    - [ ] Visual highlight badge alerts the user if vacancy salary falls below their profile threshold.

---

# Sprint 3: Status Workflow, Pipeline Dashboard & Scratchpad

## TICK-301: Symfony Workflow & Status Machine Definition
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-203
- **Scope**:
    - Configure `vacancy_status` state machine in `config/packages/workflow.yaml`:
        - States: `BOOKMARKED`, `APPLYING`, `APPLIED`, `INTERVIEWING`, `NEGOTIATING`, `ACCEPTED`, `I_WITHDREW`, `NOT_SELECTED`, `NO_RESPONSE`.
        - Allowed transitions: defined logically (e.g., `BOOKMARKED` -> `APPLYING` / `APPLIED` / `I_WITHDREW`).
    - Implement guard listeners preventing invalid transitions.
- **Acceptance Criteria**:
    - [ ] Workflow state machine prevents illegal status jumps (e.g., direct jump from `BOOKMARKED` to `ACCEPTED`).
    - [ ] State transitions can be triggered programmatically and via UI buttons.

---

## TICK-302: Audit History & Transition Event Logging
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-301
- **Scope**:
    - Create `VacancyStatusHistory` entity (`id`, `vacancy_id`, `from_status`, `to_status`, `notes`, `transitioned_at`).
    - Create Symfony Workflow event listener (`workflow.vacancy_status.transition`) capturing transition events and creating audit records.
    - Add optional modal prompt when changing status to capture notes (e.g., "First screening scheduled").
- **Acceptance Criteria**:
    - [ ] Every status change writes an audit record with timestamp and target status.
    - [ ] Transition timeline displays cleanly on the vacancy detail page.

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
    - [ ] Chevron visual matches requested pipeline UI design (active highlighting, clean borders, responsive wrapping).
    - [ ] Clicking a chevron filters vacancies dynamically via Stimulus / Turbo frame without full page reload.

---

## TICK-304: Vacancy Overview Table & Advanced Search/Filter View
- **Epic**: Epic 3 (Pipeline & Workflow Engine)
- **Dependencies**: TICK-303
- **Scope**:
    - Build primary Dashboard Overview view (`/app/vacancies`).
    - Implement search bar (by Title, Company Name, Tech Stack).
    - Add filter controls: Status, Excitement Rating (0-5 stars), Work Mode, Salary Fit.
    - Table columns: Job Title, Company/Recruiter, Work Mode, Tech Stack Tags, Excitement, Salary, Next Action, Current Status pill.
- **Acceptance Criteria**:
    - [ ] Table renders fast with server-side pagination (20 items per page).
    - [ ] Quick actions menu per row: View details, Change status dropdown, Archive, Delete.

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
    - [ ] Scratchpad notes save asynchronously upon user edit/blur.
    - [ ] Vacancies with upcoming or overdue `next_action_at` display alert callouts on the dashboard.

---

# Sprint 4: Outbox Messaging, Async Workers & Optimization

## TICK-401: Symfony Messenger & Doctrine Outbox Infrastructure
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: TICK-302
- **Scope**:
    - Configure Symfony Messenger with Doctrine transport outbox table (`messenger_messages`).
    - Routing domain event messages (e.g., `VacancyStatusChangedEvent`, `ResumeUploadedEvent`) to async outbox queue.
    - Setup CLI worker consumer command script (`bin/console messenger:consume async`).
- **Acceptance Criteria**:
    - [ ] Dispatching domain events writes to the database outbox synchronously without delaying the HTTP request.
    - [ ] Worker process picks up and processes outbox messages reliably.

---

## TICK-402: Async Status Logging & Storage Notification Workers
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: TICK-401
- **Scope**:
    - Refactor status history writing logic to execute asynchronously via Messenger Outbox handler.
    - Add async file validation listener for newly uploaded resume files.
- **Acceptance Criteria**:
    - [ ] HTTP responses for status updates complete in under 15ms.
    - [ ] Audit logs and file post-processing tasks complete asynchronously in background.

---

## TICK-403: Responsive Polish & NAS Docker Performance Tuning
- **Epic**: Epic 4 (Outbox & Performance)
- **Dependencies**: All prior tickets
- **Scope**:
    - Test UI on mobile, tablet, and desktop breakpoints using Flowbite responsive utilities.
    - Optimize MariaDB indexes on `user_id`, `status`, `next_action_at`, and `date_added`.
    - Validate memory footprint under FrankenPHP worker mode on Synology DS416play (target memory usage under 250MB RAM).
- **Acceptance Criteria**:
    - [ ] Dashboard pipeline and vacancy tables fully responsive on mobile viewports.
    - [ ] Container memory usage remains steady under peak execution.