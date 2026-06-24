# Frontend rules

## General

- Respect the existing frontend architecture and conventions.
- Reuse existing components, hooks, services, utilities, and styles before creating new ones.
- Do not introduce a new UI library without explicit approval.
- Keep components small, readable, and focused.
- Prefer simple, maintainable code over clever abstractions.

## React

- Prefer functional components.
- Use TypeScript when the project supports it.
- Keep side effects inside appropriate hooks.
- Avoid unnecessary global state.
- Do not duplicate API calls across components; use existing service or hook patterns.
- Keep forms predictable and validate user input.

## Styling

- Follow the existing Tailwind, shadcn, or CSS conventions.
- Avoid inline styles unless necessary.
- Keep responsive behavior in mind.
- Do not change the global design system unless explicitly requested.
- Keep UI changes consistent with the existing visual style.

## API calls

- Keep API calls in dedicated services, clients, or hooks when the project already uses that pattern.
- Handle loading, empty, and error states.
- Do not silently swallow errors.
- Avoid hardcoded API URLs; use existing environment configuration.

## Quality

- Remove unused imports, variables, components, and files.
- Do not leave debug logs, temporary UI text, or commented-out experiments.
- Keep accessibility in mind for buttons, forms, labels, and navigation.
