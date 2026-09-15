# Security Policy

Please **do not** open a public issue for a vulnerability.

## Report

Use [GitHub Security Advisories](https://docs.github.com/en/code-security/security-advisories/working-with-security-advisories/privately-reporting-a-security-vulnerability) on this repository if it is hosted on GitHub. Include:

- Affected version / commit
- Steps to reproduce
- Impact (who can do what)

We aim to reply within 7 days.

## Scope

In scope: admin auth, member auth, install wizard, file upload, cache/backup restore, multi-site isolation.

Out of scope: demo passwords in local seeders, missing GD on a developer machine, theme XSS in third-party templates you add yourself.

## Production

Never keep the seeded accounts (`admin@example.com` / `Admin@12345` and friends) on a public site. Set `APP_DEBUG=false`, `APP_ENV=production`, and a unique `APP_KEY`. Delete `storage/app/install.lock` only when you intend to reinstall.
