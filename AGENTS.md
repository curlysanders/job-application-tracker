# AGENTS.md

## Project overview

Job Application Tracker is a self-hosted, privacy-first Symfony application for
tracking job vacancies and applications. It is designed to run efficiently on a
home server or VPS using Docker and FrankenPHP worker mode. The project is
licensed under AGPL-3.0.

Current core stack:

- PHP 8.5 and Symfony 8.1
- MariaDB 12.3
- FrankenPHP 1.12.7 in worker mode
- Doctrine ORM, Symfony Messenger, Symfony Workflow, and Flysystem
- Twig Components, Stimulus, Flowbite, and AssetMapper (no Node.js build step)

## Dependency and configuration conventions

- When working with package versions newer than the agent's training-data
  cutoff, use the Context7 MCP to verify current documentation and follow the
  latest applicable standards.
- This project uses PHP configuration files in `config/`, rather than Symfony's
  default YAML configuration. Preserve that choice: add and update
  configuration as PHP files, and keep all configuration consistent with this
  convention.

## Tooling

- Prefer PhpStorm MCP tools when available over internal tools whenever the PhpStorm tools can complete the task.

## Local development

Use Docker Compose for the application runtime. First-time setup is:

```bash
cp .env.local.example .env.local
docker compose build app
docker compose run --rm --no-deps app composer install
docker compose up -d
```

Set a unique `APP_SECRET` in `.env.local`. The MariaDB credentials in the
checked-in configuration are development defaults and only initialize a fresh
database volume.

Useful checks:

```bash
docker compose exec app php bin/console about
docker compose exec app php bin/console dbal:run-sql 'SELECT VERSION(), 1'
docker compose exec app composer tests
docker compose exec app composer analyse
docker compose exec app composer ci:php-cs-fixer
```

Run formatters/refactors deliberately, as they modify files:

```bash
docker compose exec app composer fix
```

FrankenPHP workers keep PHP code and configuration in memory. After changing
PHP, configuration, dependencies, or adding Stimulus controllers, restart the
app container:

```bash
docker compose restart app
```

After changing Compose settings, use:

```bash
docker compose up -d --no-deps app
```

The development app is served at `http://localhost/`. Component previews are
available at `/_components` only in `dev` and `test`. For production, run
`APP_ENV=prod APP_DEBUG=0 php bin/console asset-map:compile` inside the
deployment container. Do not keep `public/assets/` from a production build when
working on development assets, because published assets take precedence.

## Architecture and implementation conventions

- Apply pragmatic clean architecture: domain business rules, status transitions,
  and salary/commute matching belong in the domain; Doctrine entities may serve
  directly as domain models for internal CRUD.
- Keep strict ports/adapters boundaries around external integrations: Flysystem
  storage, Messenger outbox/messaging, and future AI services.
- Use CQRS Lite: application services and Messenger commands handle writes;
  repositories provide focused read queries/DTOs for dashboard and component
  rendering. Avoid mapper layers that do not provide a concrete benefit.
- Use relational audit logging, not event sourcing. Symfony Workflow listeners
  record every vacancy transition in `vacancy_status_history`.
- Use Symfony Workflow as the source of truth for the vacancy state machine:
  `BOOKMARKED → APPLYING → APPLIED → INTERVIEWING → NEGOTIATING → ACCEPTED`,
  with terminal outcomes `I_WITHDREW`, `NOT_SELECTED`, and `NO_RESPONSE`.
- Preserve standardized TechStack lookup tags rather than free-form technology
  strings. Use ISO 4217 currency codes and the documented work/transport enums.
- Store the active resume through Flysystem; do not couple domain logic to a
  local filesystem path.
- Inject controller dependencies through promoted constructor properties. Keep
  controller action parameters for request, route, and other runtime values;
  do not use action-method injection for services.
- Use invokable, single-action controllers: create one controller per HTTP
  endpoint or use case and expose `__invoke()` as its only public action.
  Private helper methods remain allowed.
- Favor direct constructor injection over extending `AbstractController`.
  Controllers may extend it only when a specific helper provides a clear
  benefit; inject Twig, forms, routing, security, sessions, and other framework
  services explicitly for rendering and related request handling.
- Prefer Twig Components and Stimulus controllers for UI behavior. AssetMapper
  manages frontend assets; do not add a Node/Tailwind compilation workflow.

## Domain guardrails

- A vacancy is the central aggregate. It links to a company, recruiter(s), tech
  stacks, status-history audits, source URLs, structured job-summary blocks,
  salary/work-mode details, and interview scratchpad/next-action data.
- Companies and recruiters are reusable standalone entities, allowing multiple
  vacancies to reference the same organization or contact.
- Record terminal transition reasons for withdrawals and rejections where
  applicable. Keep scratchpad notes as Markdown-rich text.
- Salary preference is gross monthly pay; commute preference is a one-way travel
  duration.

## Git workflow

- `main` is the only long-lived branch. Do not develop directly on it.
- Start work from an up-to-date `main` and create a short-lived branch using one
  of: `feature/`, `fix/`, `chore/`, `refactor/`, or `docs/`.
- Push the branch and open a pull request targeting `main`. PRs must have CI,
  be current with `main`, and have all conversations resolved before merge.
- Use squash merge and delete the feature branch afterward.
- Commits on non-`main` branches must be verified signed. The resulting squash
  commit on `main` is intentionally not required to be signed.
- Production is the GitHub `production` environment targeting the Synology, not
  a Git branch. CD is future work; do not assume a deployment pipeline exists.

## Scope awareness

The current application is Phase 1: manual entry, pipeline visualization,
entity modeling, workflow audit history, and outbox groundwork. AI parsing and
matching, external scraping/distance services, OAuth, cloud storage, and CD are
future phases. Do not introduce these capabilities unless the task explicitly
requires them.
