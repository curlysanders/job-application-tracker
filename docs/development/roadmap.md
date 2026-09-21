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

Before the first production deployment, add a dedicated production image and
Compose configuration. The current Docker setup is intentionally development
oriented and must not be used unchanged for an internet-accessible deployment.

- Build a minimal production image without Xdebug, Git, development dependencies,
  or bind-mounted source code.
- Do not publish MariaDB to host interfaces by default; use non-default secrets
  supplied through the deployment environment.
- Define a production response-header policy, including CSP, frame restrictions,
  Referrer Policy, and production-only HSTS.
- Pin third-party GitHub Actions to audited commit SHAs.
