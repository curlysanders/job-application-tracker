# Sprint 5: AI Foundation & Safe Local Inference

## TICK-501: Symfony AI Provider Ports & Local Model Gate
- **Epic**: Epic 5 (AI Foundation & Safe Local Inference)
- **Dependencies**: TICK-403
- **Scope**:
    - Add Symfony AI Platform, Bundle, and provider configuration using the
      project's PHP configuration convention.
    - Define application ports for vacancy extraction and candidate fit; add
      Infrastructure adapters for Ollama, GLiNER extraction, and test fakes.
    - Configure opt-in local inference, including an Ollama adapter limited to
      one concurrent request and a 2,048-token context.
    - Provide an optional resource-bounded CPU-inference Ollama Compose profile for
      local validation, keeping model traffic inside the Compose network.
    - Build a curated Dutch and English vacancy corpus and NAS benchmark that
      measures model memory, swap activity, app/worker health, latency, and
      structured extraction quality.
- **Acceptance Criteria**:
    - [x] The app can select fake or local providers without leaking provider
      details into domain code.
    - [x] A development-host GLiNER run validates all 24 corpus responses and
      meets the documented extraction accuracy thresholds.
    - [x] CI uses fakes or recorded responses and makes no real provider calls.
    - [ ] Before enabling GLiNER on the NAS, validate all corpus responses and
      meet the accuracy thresholds in two runs with the exact deployment images.
    - [ ] Before enabling GLiNER on the NAS, verify host memory headroom, swap,
      OOM and restart behavior, and gateway, application, and worker health
      throughout the admission benchmark.
- **Validation**: [Provider boundary, corpus gate, and check commands](tick-501-validation.md).

---

## TICK-502: Asynchronous AI Analysis Requests & Secure Result Storage
- **Epic**: Epic 5 (AI Foundation & Safe Local Inference)
- **Dependencies**: TICK-501
- **Scope**:
    - Persist tenant-owned analysis requests, results, state, input/result
      fingerprints, provider/model metadata, and expiry timestamps.
    - Dispatch analysis work through the transactional outbox with messages
      containing only request IDs and fingerprints.
    - Make redelivery idempotent, replace obsolete results when inputs change,
      and delete discarded or unfinished drafts after 24 hours.
- **Acceptance Criteria**:
    - [x] A user can access only their own analysis requests and results.
    - [x] Duplicate outbox delivery produces one result for a request version.
    - [x] Events, logs, and failures never contain resume contents, vacancy
      text, full addresses, or provider credentials.
- **Validation**: [Encrypted request storage, expiry sweep, and check commands](tick-502-validation.md).

---

# Sprint 6: Vacancy Extraction & Candidate Fit

## TICK-601: Evidence-Backed Vacancy Extraction Review
- **Epic**: Epic 6 (Vacancy Extraction & Candidate Fit)
- **Dependencies**: TICK-502
- **Scope**:
    - Add an authenticated action for submitting pasted vacancy text for
      asynchronous analysis and showing its progress.
    - Extract proposals for existing vacancy fields with source excerpts,
      confidence, validated enums, and standardized tech-stack suggestions.
    - Add a review screen that lets the user select proposals and pre-fills the
      existing unsaved vacancy form. Company and recruiter records remain
      explicitly user-controlled.
- **Acceptance Criteria**:
    - [x] Analysis never creates or changes a vacancy before the user submits
      the existing vacancy form.
    - [x] Unsupported enum values, ungrounded values, and uncertain fields are
      rejected or left empty.
    - [x] Users can see the source excerpt for every accepted proposal.
    - [ ] After NAS admission and activation, exercise an authenticated
      synthetic extraction through review and verify the review response.
- **Validation**: [Vacancy extraction review and check commands](tick-601-validation.md).

---

## TICK-602: Resume Text Extraction & Transparent Candidate Fit
- **Epic**: Epic 6 (Vacancy Extraction & Candidate Fit)
- **Dependencies**: TICK-502
- **Scope**:
    - Extract text deterministically from validated active PDF and DOCX resumes
      through Flysystem without placing file paths or contents in events.
    - Calculate salary fit using existing user preference rules and produce an
      explainable resume-fit report with strengths, gaps, and evidence.
    - Offer Gemini 2.5 Flash-Lite only after a user confirms each run; show the
      provider, model, data sent, and estimated cost before submission.
- **Acceptance Criteria**:
    - [ ] Missing, invalid, or pending resumes produce a clear unavailable
      state and never invoke an AI provider.
    - [ ] Cloud analysis is impossible without the per-run consent action.
    - [ ] Resume-fit reports expose evidence and freshness rather than a single
      opaque score.

---

## TICK-603: Fit Results in Vacancy Detail & Dashboard
- **Epic**: Epic 6 (Vacancy Extraction & Candidate Fit)
- **Dependencies**: TICK-601, TICK-602
- **Scope**:
    - Display extraction provenance and current salary/resume fit reports on
      vacancy detail pages.
    - Add dashboard filters and compact status indicators for salary and resume
      fit, including pending, unavailable, and stale states.
- **Acceptance Criteria**:
    - [ ] Dashboard and vacancy-detail UI remain responsive and accessible at
      the supported Phase 1 viewport widths.
    - [ ] Filters and indicators distinguish unavailable data from a negative
      match.

---

# Sprint 7: Location & Commute Matching

## TICK-701: Secure Commute Profile & Route Result Storage
- **Epic**: Epic 7 (Location & Commute Matching)
- **Dependencies**: TICK-502
- **Scope**:
    - Add an encrypted full commute origin to the user profile and a confirmed
      commute destination to vacancies while retaining the existing display
      location field.
    - Add a route-provider port and persist tenant-owned, cacheable route
      results keyed by non-reversible address fingerprints.
    - Track Google Routes Essentials usage in the application and stop new
      requests at 9,500 monthly calls, resetting at midnight Pacific time on
      the first day of each month.
- **Acceptance Criteria**:
    - [ ] Plaintext commute origins never appear in logs, events, or cache keys.
    - [ ] Route usage cannot exceed the application-side monthly cap.
    - [ ] A missing confirmed destination produces an unavailable result rather
      than a city-centre estimate.

---

## TICK-702: Commute Calculation & Fit Presentation
- **Epic**: Epic 7 (Location & Commute Matching)
- **Dependencies**: TICK-603, TICK-701
- **Scope**:
    - Implement Google Routes Compute Routes Essentials for the user's selected
      car, public-transport, bicycle, or walking preference.
    - Calculate public-transport and other route estimates for the next weekday
      at 08:30 Europe/Amsterdam by default, with a user-triggered refresh for a
      chosen departure time.
    - Evaluate the result against maximum one-way commute minutes and present
      it on vacancy detail and dashboard views.
- **Acceptance Criteria**:
    - [ ] Remote vacancies are marked not applicable; hybrid and onsite roles
      are evaluated only when a confirmed destination exists.
    - [ ] Provider failures, expired cache entries, and quota exhaustion are
      visible as unavailable states without blocking vacancy authoring.
    - [ ] Cached results, transport mode, departure time, duration, distance,
      and calculation time are visible to the user.
