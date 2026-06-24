# Security rules

## Secrets

- Never hardcode secrets, tokens, passwords, private keys, API keys, or credentials.
- Never commit `.env` files containing real values.
- Use `.env.example` or documentation for required variables.
- Do not print secrets in logs, scripts, tests, or documentation.
- Do not expose private keys or JWT secrets.

## Authentication and authorization

- Do not disable authentication or authorization checks to make something work.
- Check permissions on every sensitive operation.
- Do not rely only on frontend checks for security.
- Keep admin-only endpoints protected.
- Avoid exposing user roles or permissions beyond what the frontend needs.

## Input and output

- Validate and sanitize user input.
- Avoid SQL injection, command injection, path traversal, and unsafe deserialization.
- Escape output where required by the framework.
- Do not expose stack traces, internal paths, or implementation details to users.

## Infrastructure

- Do not expose databases, admin panels, dashboards, or internal tools publicly unless explicitly requested and protected.
- Use least privilege for service accounts, database users, and containers.
- Avoid running containers as root when the project allows a non-root user.
- Do not disable TLS verification unless explicitly requested for a local/debug-only context.

## Dependencies

- Do not add unnecessary dependencies.
- Prefer maintained and trusted packages.
- Do not ignore known critical vulnerabilities without explanation.
