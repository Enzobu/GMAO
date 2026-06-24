# Documentation rules

## General

- Keep documentation practical, accurate, and project-specific.
- Prefer clear commands that can be copied and run.
- Do not document behavior that has not been implemented.
- Update documentation when setup, commands, ports, environment variables, or deployment steps change.
- Write release notes in French unless explicitly requested otherwise.

## README

- Keep setup instructions up to date.
- Document required services and ports.
- Document required environment variables using placeholders, never real secrets.
- Include common development commands.
- Mention Docker commands when the project is containerized.

## Technical documentation

- Explain why important architectural choices exist.
- Keep diagrams and examples aligned with the actual code.
- Do not over-document obvious code.
- Document non-obvious configuration, workflows, and deployment behavior.

## Release notes

- Group changes by theme.
- Mention new features, fixes, technical changes, and breaking changes.
- Keep wording clear and user-oriented.
- Do not invent changes that are not present in the code, PR, or changelog.

## Comments

- Add comments only when they clarify non-obvious logic.
- Do not add comments that simply repeat the code.
- Remove outdated comments.
