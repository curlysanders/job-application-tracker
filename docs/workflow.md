# Git & CI/CD Development Workflow

This document describes the GitHub workflow and CI/CD foundation for the project.

The setup is intentionally kept simple:

- `main` is the only long-lived branch.
- Feature work is done on short-lived branches.
- Changes reach `main` through pull requests.
- `main` is protected.
- CI validates changes before they are merged.
- Production is represented by a GitHub **environment**, not a branch.
- The only production target is currently the Synology.
- CD/deployment will be added later, when the required infrastructure exists.

---

## 1. Branching Model

There is only one permanent branch:

```text
main
```

Development happens on short-lived branches created from `main`.

Recommended branch prefixes:

```text
feature/*
fix/*
chore/*
refactor/*
docs/*
```

Examples:

```text
feature/job-search
fix/vacancy-parser
chore/ci-foundation
docs/setup
```

There is deliberately **no**:

```text
develop
staging
release/*
hotfix/*
production
```

For this project, these branches would add complexity without providing a useful additional environment or release stage.

---

## 2. Standard Development Workflow

The normal development loop is:

```text
main
 │
 │ git switch -c feature/foo
 ▼
feature/foo
 │
 │ development
 │ signed commits
 │ git push
 ▼
GitHub PR
 │
 ├── CI ✓
 ├── branch up-to-date ✓
 └── conversations resolved ✓
 │
 │ Squash & merge
 ▼
main
 │
 └── feature branch deleted
```

### Starting New Work

Always start from an up-to-date `main`:

```bash
git switch main
git pull
git switch -c feature/foo
```

Work normally on the branch and push it to GitHub:

```bash
git push -u origin feature/foo
```

Create a pull request targeting `main`.

After the PR has passed its required checks and review/conversations are complete, use **Squash and merge**.

The feature branch should then be deleted.

---

## 3. Protected `main`

`main` is the central integration branch and should not be used for direct development.

The GitHub ruleset should enforce:

- Pull request required
- Required CI status checks
- Branch must be up to date before merging
- Conversations must be resolved
- Force pushes prohibited
- Branch deletion prohibited
- Squash merge as the preferred/allowed merge strategy

### Important

The required CI status check cannot be configured until the CI workflow actually exists.

Therefore, CI should be added before making the CI check mandatory.

---

## 4. Commit Signing

All branches except `main` require verified signed commits.

This is enforced through a GitHub ruleset targeting all branches except `main`.

The purpose is to establish commit provenance during development: commits on feature and other short-lived branches can be cryptographically verified as having been created by the holder of the corresponding signing key.

`main` does not require signed commits.

This is intentional. Pull requests, CI, branch protection, and the merge process provide the relevant controls for changes entering `main`. When a PR is squash-merged, GitHub creates a new squash commit, so requiring that resulting commit to be individually signed provides limited additional value for this workflow.

The expected workflow is therefore:

```text
feature/foo
    │
    ├── signed commit
    ├── signed commit
    └── signed commit
            │
            ▼
           PR
            │
            ├── CI
            ├── review
            └── conversations resolved
            │
            ▼
       Squash & merge
            │
            ▼
           main
```
---

## 5. GitHub Production Environment

Production is represented as a **GitHub Environment**:

```text
production
```

It is **not** represented by a Git branch.

The eventual flow is:

```text
main
 │
 ├── CI
 │
 └── CD
      │
      ▼
 production environment
      │
      ▼
   Synology
```

This keeps the Git model simple while still allowing GitHub to apply deployment-specific settings later, such as:

- Environment secrets
- Environment variables
- Deployment protection rules
- Deployment history
- Branch/tag deployment restrictions

There is currently no staging environment because there is no staging target.

---

## 6. Future CI

CI will be introduced once the relevant project components exist.

The eventual CI pipeline will look approximately like this:

```text
CI
├── Composer validate
├── PHPStan
├── PHP-CS-Fixer
├── PHPUnit
├── Symfony checks
├── frontend checks
└── Docker image build
```

As the project grows, these can be separated into independent jobs:

```text
CI
├── static-analysis
├── tests
├── frontend
└── docker
```

There is no need to create these checks prematurely.

The CI foundation should initially only establish that GitHub Actions is working and that the PR workflow is functioning correctly.

---

## 7. Future CD

Deployment will eventually follow this general architecture:

```text
main
  │
  ▼
Build Docker image
  │
  ▼
Push image
  │
  ▼
production environment
  │
  ▼
Synology
```

The exact deployment mechanism will be decided later, when the Docker setup, registry, Synology configuration, secrets, and deployment mechanism are ready.

### Deployment Trigger

Initially, deployment could trigger from `main`:

```text
main → production
```

Once the project has a proper release/versioning strategy, deployment on version tags may be preferable:

```text
v0.1.0 → production
v0.2.0 → production
v1.0.0 → production
```

This decision can be made when the first usable version exists.

---

## 8. Final Architecture

The intended overall architecture is:

```text
                    ┌────────────────────┐
                    │      feature/*     │
                    │       fix/*        │
                    │      chore/*       │
                    │     refactor/*     │
                    │       docs/*       │
                    │                    │
                    │ signed commits ✓   │
                    └─────────┬──────────┘
                              │
                              │ PR
                              ▼
                    ┌────────────────────┐
                    │        main        │
                    │      protected     │
                    |                    |
                    │ signed commits: no │
                    └─────────┬──────────┘
                              │
                     ┌────────┴────────┐
                     │                 │
                    CI              CD later
                     │                 │
                     │                 ▼
                     │           production
                     │                 │
                     │                 ▼
                     │             Synology
                     │
                     ▼
                  GitHub
```

The important distinction is:

```text
Branch:       main
Environment:  production
Target:       Synology
```

There is no `production` branch.

---

## 9. Rules of Thumb

### Do

- Keep `main` always releasable.
- Create short-lived branches from `main`.
- Sign commits on development branches.
- Use pull requests for all changes.
- Keep PRs focused on one logical change.
- Squash merge PRs.
- Delete merged feature branches.
- Let CI validate changes before merging.
- Treat production as an environment, not a branch.

### Don't

- Commit directly to `main`.
- Create a `develop` branch.
- Create a `staging` branch when there is no staging environment.
- Create a `production` branch.
- Introduce release/hotfix branches without a concrete need.
- Build the complete CI/CD pipeline before the required project components exist.

---

## 10. Current State vs. Future State

### Now

```text
GitHub
  │
  └── main (protected)
        ▲
        │ PR
        │
   feature/* etc.
```

With the initial CI foundation:

```text
GitHub
  │
  ├── main (protected)
  │     ▲
  │     │ PR
  │     │
  │   feature/*
  │
  └── GitHub Actions
```

### Later

```text
GitHub
  │
  ├── main (protected)
  │     │
  │     ├── CI
  │     │
  │     └── CD
  │           │
  │           ▼
  │       production
  │           │
  │           ▼
  │        Synology
  │
  └── GitHub Actions
```

---

## 11. Target End State

The desired end state is intentionally simple:

```text
Short-lived branches
        │
        ▼
   Pull Request
        │
        ▼
   Required CI
        │
        ▼
Protected main
        │
        ▼
Production deployment
        │
        ▼
     Synology
```

**One permanent branch, one production environment, one production target.**

This provides a clean foundation without introducing branching or deployment complexity that the project does not currently need.
