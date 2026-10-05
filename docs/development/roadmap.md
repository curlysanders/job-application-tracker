# Future Phases Roadmap (Preview)

## Phase 2: Symfony AI Integration & Matching Engine
- Automated vacancy text parsing: Paste raw text and let Symfony AI parse summaries, salaries, and tech stack tags automatically.
- Resume Analysis: Extract key tech competencies from uploaded resume files.
- Automated Vacancy Ranking: Score vacancies based on resume match percentage, salary fit, and preferred stack.

## Phase 3: External API Extensions & Cloud Migration
- Location & Commute API (Google Maps / OpenStreetMap distance matrix integration).
- OpenGraph URL scraper to auto-fill vacancy titles and company logos from pasted URLs.
- Google OAuth / SSO integration.
- S3 cloud storage migration via Flysystem configuration switch.

## Production deployment hardening

The public repository now provides a dedicated production image. Its deployment
configuration lives in the private `job-application-tracker-deploy` repository;
the development Compose stack remains unsuitable for an internet-accessible
deployment.

- The production image contains no Xdebug, Git, development dependencies, or
  bind-mounted source code.
- The private Compose stack does not publish MariaDB and supplies non-default
  credentials through SOPS-encrypted environment values.
- The private Caddy configuration defines CSP, frame restrictions, Referrer
  Policy, `nosniff`, and HSTS.
- The public image workflow uses a commit-pinned checkout action and Docker CLI
  commands for the remaining release operations.
