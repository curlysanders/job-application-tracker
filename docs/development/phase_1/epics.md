# Epics Definition

## Epic 1: Project Scaffold, Auth & User Preferences
- **Goal**: Establish the FrankenPHP + Symfony 8.1 containerized environment, authentication framework, user profile configuration, and Flysystem resume storage.
- **Key Deliverables**:
    - Symfony 8.1 bootstrap with PHP 8.5 compatibility.
    - User registration & login via Symfony Security.
    - User profile form (gross min salary, max commute minutes, transport mode).
    - Single resume upload feature backed by Flysystem abstraction.

## Epic 2: Domain Data Structure & Manual Vacancy Authoring
- **Goal**: Implement standard entities (Company, Recruiter, TechStack, Vacancy) and create an intuitive, multi-tab manual entry form for pasting and structuring vacancy details.
- **Key Deliverables**:
    - Doctrine migrations and entity definitions for domain entities.
    - Company and Recruiter management interfaces.
    - Multi-section Vacancy creation form with dynamic contact linking, tech stack tagging, and structured summary blocks.
    - Visual indicator badges comparing vacancy salary/location against user preferences.

## Epic 3: Interactive Pipeline Dashboard & Workflow State Machine
- **Goal**: Build the core visual interface featuring the horizontal status chevron component, audit logging, filtering, and detail scratchpad.
- **Key Deliverables**:
    - Symfony Workflow state machine configuration defining allowed transition paths.
    - Event listeners logging status changes into `vacancy_status_history` with transition notes.
    - Twig Component: Horizontal Chevron Pipeline display with active count badges and state filtering click actions.
    - Dynamic Vacancy table with sorting, search, stack tag filters, and threshold highlight badges.
    - Vacancy Detail page with rich text scratchpad notes and next action date reminders.

## Epic 4: Asynchronous Outbox Architecture & Performance Tuning
- **Goal**: Integrate Symfony Messenger with Doctrine transport for async background processing of outbox events and validate performance on Synology DS416play.
- **Key Deliverables**:
    - Messenger Outbox pattern configuration.
    - Async worker handling for status history logging and background file processing.
    - Memory consumption and execution latency benchmark on local Docker setup.
