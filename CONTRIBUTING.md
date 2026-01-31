# Contributing Guidelines

This document describes the key rules and recommendations for developers working on the project.

## Git Workflow

We use a simplified GitFlow.

- `main` - the stable branch that contains production-ready code.
- `develop` - the integration branch for new features. All developers merge their changes here.
- `feature/<scope>-<short-desc>` - branches for developing new functionality.
- `bugfix/<scope>-<issue-id>` - branches for fixing bugs.

### Branch Naming

- **Scope (context):** A short identifier for the module or area (e.g., `auth`, `api`, `ui`, `docs`).
- **Short description:** A brief description in `kebab-case`.

Example: `feature/auth-add-oauth-support`

## Issues

We use issues for project work planning and tracking tasks, bugs, and enhancements.

### Issue Naming

The issue title follows this pattern:

`PREFIX-ID Short Title`

- **PREFIX** maps to a domain/module relevant to your project (e.g., `CORE`, `API`, `UI`, `DB`, `AUTH`, `DOC`, `TEST`).
- **ID** is a numeric identifier within the domain (e.g., `001`, `042`, `150`).
- **Short Title** is the high-level scope shown in the issue list.

Examples:
- `CORE-005 Implement Plugin System`
- `UI-042 Redesign Dashboard Layout`
- `AUTH-150 Add Two-Factor Authentication`

### Issue Body Structure

The issue body uses a more detailed heading that is not the same as the issue title.

Recommended template:
```
# Detailed Execution Heading

## User Story
As a <role>, I want <capability> so that <benefit>.

## Acceptance Criteria
1. ...
2. ...
3. ...
```

### Labels

Labels are assigned based on the type and intent of work:

- `documentation` for docs, content, or guidelines work.
- `enhancement` for new features or improvements.
- `bug` for bug reports and fixes.
- `help wanted` for work that benefits from extra contributors.
- `good first issue` only for starter-friendly tasks.

## Commit Style

We use a style inspired by [Conventional Commits](https://www.conventionalcommits.org/) with project-specific rules.

**Format:** `type: Message` or `type(scope): Message`

- **scope:** Optional context (e.g., `api`, `auth`, `ui`, `docs`).
- **type:** The type of change:
  - `feat`: A new feature.
  - `fix`: A bug fix.
  - `refactor`: Code refactoring without changing behavior.
  - `test`: Adding or fixing tests.
  - `docs`: Documentation changes.
  - `chore`: Routine tasks (dependency updates, CI setup).
- **Message** starts with a capital letter and is a short sentence that may include an action verb.
- **One line only.** We do not use commit bodies.
- **Atomic commits.** Each commit should represent a single logical change.

**Examples:**
```
feat: Add user registration endpoint
fix(auth): Resolve token expiration bug
docs: Add setup instructions for development
```

## Pull Requests (PR)

1. **Create a PR** to merge your `feature` or `bugfix` branch into `develop`.
2. The **PR description** should include a link to the related issue and a short summary of changes.
3. **Code Review:** Every PR must be reviewed by at least one other developer.
4. **Merging:** After a successful review and all checks pass, the PR can be merged into `develop`.
