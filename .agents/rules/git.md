# Git rules

## General
- Never commit without explicit user request.
- Never push without explicit user request.
- Never rewrite history unless explicitly requested.
- Always check `git status` before preparing a commit.
- Keep commits small and focused.

## Branches
- Use short, descriptive branch names.
- Branch names should use kebab-case.
- Prefer prefixes:
  - `feat/`
  - `fix/`
  - `refactor/`
  - `docs/`
  - `ci/`
  - `chore/`

Examples:
- `feat/add-user-dashboard`
- `fix/docker-prod-build`
- `ci/update-sonar-workflow`

## Commit messages
- Use Conventional Commits.
- Format can be either:
  - `<type>: <subject>`
  - `<type>(<scope>): <subject>`
- Scope is optional.
- Do not require a fixed list of scopes.
- Commit messages must be in English.

Allowed types:
- `feat`
- `fix`
- `refactor`
- `test`
- `docs`
- `chore`
- `ci`
- `style`

Examples:
- `fix: correct production build output`
- `fix(front): correct PDF compressor link`
- `feat: add vehicle history export`
- `ci: update deploy workflow`
- `docs: add release notes for 1.3.0`

## Commit content
- Do not mix unrelated changes in the same commit.
- Do not include debug code, temporary logs, or commented-out experiments.
- Do not commit `.env`, secrets, credentials, private keys, dumps, or generated build artifacts.
- Do not commit dependency lockfile changes unless dependencies were intentionally updated.

## Pull requests
- PR titles should follow Conventional Commits.
- PR descriptions should include:
  - What changed
  - Why it changed
  - How it was tested
- Mention breaking changes clearly.

## Safety
- Before suggesting a commit, summarize changed files.
- Before destructive commands, ask for confirmation.
- Never run:
  - `git reset --hard`
  - `git clean -fd`
  - `git push --force`
  - `git rebase`
  unless explicitly requested.