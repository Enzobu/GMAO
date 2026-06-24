# Monorepo rules

- Respect app and package boundaries.
- Do not duplicate shared types between apps.
- Put shared contracts, DTOs, schemas, and types in the existing shared package when appropriate.
- Do not create circular dependencies between packages.
- Run commands from the correct workspace.
- Prefer workspace-level commands when the change affects multiple packages.
- Mention which app or package is affected by a change.