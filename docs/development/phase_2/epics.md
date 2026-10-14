# Epics Definition

## Epic 5: AI Foundation & Safe Local Inference
- **Goal**: Establish a privacy-preserving, asynchronous Symfony AI foundation
  that can run on the existing NAS without affecting the application runtime.
- **Key Deliverables**:
    - PHP-based Symfony AI configuration, provider ports, structured-output
      validation, and fake providers for automated tests.
    - Local Ollama support for `qwen3:0.6b`, limited to one concurrent request
      and a 2,048-token context.
    - A NAS acceptance benchmark covering memory, swap, application/worker
      health, latency, and a curated vacancy-extraction corpus.
    - Idempotent, tenant-owned analysis request and result records processed
      through the existing transactional outbox. Durable events carry IDs and
      fingerprints only.

## Epic 6: Vacancy Extraction & Candidate Fit
- **Goal**: Help users turn pasted vacancy text into a reviewable authoring
  draft and understand its fit with their validated resume and preferences.
- **Key Deliverables**:
    - Evidence-backed extraction of the existing vacancy fields, including
      salary, dates, work mode, text blocks, and standardized tech-stack tags.
    - An editable review flow that pre-fills the existing vacancy form without
      creating or modifying a vacancy until the user submits that form.
    - Deterministic salary fit plus an explainable resume-fit report with
      strengths, gaps, and source evidence.
    - A per-run, explicit consent flow for paid Gemini 2.5 Flash-Lite resume
      analysis; local extraction never silently falls back to a cloud model.

## Epic 7: Location & Commute Matching
- **Goal**: Calculate reliable, privacy-conscious commute fit for saved
  vacancies using the user's transport preference and maximum one-way time.
- **Key Deliverables**:
    - An encrypted full commute origin on the user profile and a separately
      confirmed commute destination on each vacancy.
    - Google Routes Compute Routes Essentials requests for driving, cycling,
      walking, and public transport, using next weekday 08:30 Europe/Amsterdam
      by default.
    - Cached route results, clear unavailable/stale/quota-exhausted states, and
      a 9,500-request application-side monthly cap under the 10,000 free
      Essentials events.
    - Commute fit displayed alongside salary and resume fit on vacancy detail
      and dashboard views.
