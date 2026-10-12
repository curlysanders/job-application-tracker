# Git workflow

`main` is the only long-lived branch. Changes reach it through focused pull
requests from short-lived `feature/`, `fix/`, `chore/`, `refactor/`, or `docs/`
branches.

## Standard workflow

```text
main
 │
 │ git switch -c feature/foo
 ▼
feature/foo
 │
 │ signed commits
 │ git push
 ▼
GitHub pull request
 │
 ├── CI passes
 ├── branch is current
 └── conversations are resolved
 │
 ▼
Squash merge to main
```

Start from an up-to-date `main`:

```bash
git switch main
git pull
git switch -c feature/foo
```

## Branch protection

The GitHub ruleset for `main` should require pull requests, current CI checks,
resolved conversations, and squash merges. Force pushes and direct work on
`main` are prohibited.

Commits on short-lived branches must be verified signed. The squash commit on
`main` is created by GitHub and is not required to be signed.
