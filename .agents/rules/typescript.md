# TypeScript rules

- Avoid `any` unless there is a strong reason.
- Prefer explicit types for public functions, API responses, and shared contracts.
- Reuse existing types before creating new ones.
- Do not duplicate backend and frontend types when shared contracts exist.
- Keep strictness aligned with the project configuration.
- Do not silence TypeScript errors with unsafe casts unless justified.