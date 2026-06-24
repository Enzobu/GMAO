# Architecture rules

## General

- Respect the existing folder structure.
- Do not introduce a new architecture without explicit approval.
- Reuse existing services, hooks, components, helpers, modules, and conventions.
- Avoid duplicate utilities when an equivalent already exists.
- Prefer simple and explicit designs.

## Boundaries

- Keep domain logic separated from technical plumbing.
- Keep UI logic out of backend code.
- Keep infrastructure details out of domain logic when the project architecture allows it.
- Do not mix unrelated responsibilities in the same file or service.

## Changes

- Make the smallest reasonable change that solves the problem.
- Avoid large refactors unless explicitly requested.
- Do not rename files, classes, routes, endpoints, or public APIs unless needed.
- Mention potential breaking changes clearly.

## Monorepo

- Respect package and app boundaries.
- Do not create cross-package imports that break the existing dependency direction.
- Put shared contracts, types, or utilities in the existing shared package when appropriate.
- Avoid duplicating types between frontend and backend when shared contracts exist.

## Maintainability

- Prefer readable code over clever code.
- Use explicit names.
- Keep files reasonably sized.
- Extract reusable logic only when it is actually reused or clearly improves readability.
