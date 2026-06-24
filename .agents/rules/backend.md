# Backend rules

## General

- Respect the existing backend architecture and conventions.
- Keep controllers thin.
- Put business logic in services or dedicated domain layers.
- Do not introduce a new architecture without explicit approval.
- Keep public API contracts stable unless a breaking change is explicitly requested.
- Validate input data explicitly.

## API

- Prefer DTOs, POJOs, Records, serializers, validators, or request objects for API contracts.
- Use DTOs for request bodies, response payloads, and public API contracts.
- Avoid exposing persistence entities directly through public APIs.
- Never expose sensitive data in API responses.
- Return clear and consistent error responses.
- Do not leak stack traces or internal implementation details to clients.
- Keep authentication and authorization checks explicit.
- Do not disable security checks to make something work.

## DTOs, POJOs and Records

- Prefer DTOs, POJOs, or Records for API input/output instead of exposing entities directly.
- Use DTOs for request bodies, response payloads, and public API contracts.
- Keep DTOs, POJOs, and Records simple and explicit.
- Use Records when immutability is useful and supported by the project or language.
- Use POJOs when framework compatibility, serialization, validation, or mutable binding requires it.
- Keep mapping logic explicit and maintainable.
- Do not put business logic inside DTOs, POJOs, or Records.
- Do not expose database entities directly unless the project already does it intentionally.

## Database

- Keep database migrations explicit and reviewable.
- Do not edit old migrations unless the project convention allows it.
- Do not silently drop data.
- Do not change column types, constraints, or relations without considering migration impact.
- Use transactions when changing multiple related records.

## Symfony / API Platform

- Follow existing entity, repository, service, controller, processor, and provider patterns.
- Keep entity validation and serialization groups consistent.
- Prefer DTOs for input and output when exposing public API operations.
- Avoid exposing entities directly when DTOs are more appropriate.
- Do not expose operations in API Platform without checking authorization and serialization.
- Prefer services for business rules instead of adding logic directly in controllers.

## Java / Spring Boot

- Prefer Records for immutable request and response DTOs when compatible with validation and serialization.
- Prefer POJOs when mutability, framework binding, or complex validation requires it.
- Do not expose JPA entities directly in controllers.
- Keep controllers thin and delegate business logic to services.
- Use explicit mappers between entities and DTOs.
- Keep validation annotations on request DTOs when possible.
- Do not put persistence logic in controllers.

## NestJS / Prisma

- Follow existing module, controller, service, DTO, and Prisma patterns.
- Use DTOs for request validation and public API contracts.
- Do not expose Prisma models directly when a response DTO is more appropriate.
- Do not bypass DTO validation.
- Keep Prisma schema changes intentional and documented.
- Run migrations instead of manually editing the database structure.

## Quality

- Remove debug logs and temporary code.
- Do not leave commented-out experiments.
- Prefer explicit names over vague names.
- Handle edge cases and errors properly.