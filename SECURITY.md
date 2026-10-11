# Security Notes

The current ARA Studio website is a static HTML, CSS, and JavaScript project. It has no server-side form handler, authentication system, database, or stored credentials.

## Reporting a Vulnerability

Do not include credentials, personal data, or exploit payloads in a public issue. Use a private, verified contact channel selected by the project owner before the repository becomes public.

## Future PHP Consultation Form

Before enabling server-side form submission:

1. Validate every field on the server with explicit length, format, and allow-list rules. Browser validation is only a usability aid.
2. Escape output for its destination context. Never insert submitted values into HTML, headers, shell commands, SQL, or logs without appropriate handling.
3. Use CSRF protection when the endpoint relies on cookies or an authenticated administrative session.
4. Add rate limiting, a honeypot or equivalent spam control, request-size limits, and abuse monitoring.
5. Prevent email header injection. Use a maintained mail library and allow-list recipient addresses on the server.
6. Keep SMTP, Telegram, database, and API credentials outside the public web root and source repository. Load them from environment variables or a server-only configuration file.
7. Return generic client-facing errors and keep detailed diagnostics in protected server logs. Disable production error display.
8. Require HTTPS, secure cookies where applicable, and restrictive file permissions on configuration and log files.
9. Configure the server to deny access to dotfiles, backups, database exports, logs, and private configuration files.
10. Apply dependency updates and security checks before introducing Composer packages.

## Deployment Review

Before each cPanel upload, inspect the archive contents and confirm that it contains only the public website files required at runtime. Never upload `.git`, `.env`, private keys, database dumps, logs, test artifacts, or local configuration.
