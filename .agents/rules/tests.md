# Testing rules

## General

- Add or update tests when changing business logic.
- Prefer existing test patterns and tools.
- Do not delete tests unless explicitly requested.
- Do not weaken assertions just to make tests pass.
- Do not claim tests passed if they were not executed.

## Running tests

- Run the smallest relevant test command first when possible.
- Run broader tests when the change affects shared logic, configuration, or public behavior.
- Mention exactly which commands were run.
- If a command cannot be run, explain why clearly.

## Test quality

- Tests should verify behavior, not implementation details, unless implementation details are the purpose of the test.
- Keep tests readable and deterministic.
- Avoid relying on test order.
- Avoid excessive mocks when a simple integration-style test is more useful.
- Cover success cases, validation errors, authorization errors, and important edge cases when relevant.

## Coverage

- Do not chase coverage with meaningless tests.
- Prioritize meaningful coverage for business rules and critical flows.
- Preserve existing coverage expectations and quality gates unless explicitly requested.

## Fixtures and data

- Keep fixtures minimal and understandable.
- Avoid hardcoded environment-specific data.
- Clean up test data when required by the project setup.
