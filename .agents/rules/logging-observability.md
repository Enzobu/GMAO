# Logging and observability rules

- Log useful operational information, not noise.
- Do not log secrets, passwords, tokens, private data, or full payloads containing sensitive data.
- Use the existing logging system.
- Keep error messages clear and actionable.
- Report errors to the existing monitoring or error tracking system when relevant.
- Do not swallow exceptions silently.
- Avoid excessive console logs in production code.