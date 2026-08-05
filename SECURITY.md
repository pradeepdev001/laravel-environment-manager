# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | ✅ Active support  |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, email **pradeepdev001@gmail.com** with:

- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Any suggested fixes

You will receive a response within 48 hours. Once confirmed, a fix will be released as soon as possible and a GitHub Security Advisory will be published.

## Security Considerations

- This package manages `.env` files which contain sensitive credentials
- Always restrict access via the `allowed_users` and `route_middleware` config keys
- Never enable `bypass_auth_in_local` in production
- Sensitive values are always masked in the UI and API by default
- Backup files can be encrypted at rest via `backup_encryption: true`
- All write operations use atomic file writes to prevent partial corruption
- CSRF protection is applied to all web routes
- API routes are protected by Sanctum authentication by default
