# Security Policy and Backend Baseline

ARA Studio currently exposes one read-only PHP page backed by static frontend assets. Phase 1 provides reusable security infrastructure but intentionally includes no form submission endpoint, admin panel, authentication, notification integration, or CMS.

## Reporting a Vulnerability

Do not include credentials, personal data, private logs, or working exploit payloads in a public issue. Use a private, verified reporting channel selected by the project owner before the repository becomes public.

## Public and Private Boundaries

Only the contents of `public/` belong in the web root. Application classes, configuration, migrations, views, CLI tools, logs, and `.env` must remain outside `public_html`. This boundary is the primary protection for internal files; server rules are defense in depth, not a substitute for the correct document root.

The production archive must never contain:

- `.env` or local configuration;
- `.git` metadata;
- private keys, certificates, API tokens, or credentials;
- SQL exports or local databases;
- logs, backups, test results, screenshots, or editor artifacts.

## Configuration and Secrets

- Real values are loaded from the private `.env` file or server environment variables.
- `.env` variants are ignored by Git; only example templates may be versioned.
- Production refuses `APP_DEBUG=true`.
- Secret values must not be included in public responses, frontend JavaScript, repository history, or deployment ZIP files.
- Rotate any credential immediately if it is accidentally disclosed, then remove it from history through an approved incident process.

## Database Safety

- Use the PDO factory in `app/Database/Connection.php`.
- Use prepared statements for every value. Never concatenate user input into SQL identifiers, clauses, or values.
- Native prepares are required and emulated prepares are disabled.
- Connections use `utf8mb4` and exception mode.
- Give the application a dedicated least-privilege MySQL user. Do not use the cPanel account password or a database root account.
- Do not expose a browser-accessible health, configuration, migration, or database-debug endpoint. `bin/database-check.php` is CLI-only and private.
- Apply numbered migrations deliberately and keep a database backup before a future schema-changing phase.

## Input Validation and Output Encoding

Future endpoints must treat every request value as untrusted, including headers, filenames, URL parameters, form bodies, cookies, and data read back from the database.

1. Validate server-side with explicit allow lists, types, byte/character length limits, and normalized formats.
2. Reject unexpected fields and unsupported content types where practical.
3. Escape output for its destination context. Use `e()` only for HTML text/attribute contexts; use the correct encoder for URLs, JSON, JavaScript, CSS, email headers, and SQL.
4. Never place raw request values in logs. Log stable event names and generated reference IDs instead.
5. Add request-size limits, rate limiting, abuse monitoring, and spam controls before accepting consultation submissions.

Browser validation is a usability feature, not a security boundary.

## Sessions and CSRF for Future Authentication

No session is started in Phase 1. Before adding authenticated administration:

- require HTTPS and set cookies with `Secure`, `HttpOnly`, and an appropriate `SameSite` value;
- enable strict session mode, use cookies only, and reject session IDs in URLs;
- regenerate the session ID after login and privilege changes;
- enforce idle and absolute expirations;
- store a cryptographically random CSRF token in the session and verify it with `hash_equals` on every state-changing request;
- validate the request origin as additional defense, not as a replacement for CSRF tokens;
- protect login and recovery flows with rate limits and audit events that do not contain passwords or tokens.

## Errors and Logging

The centralized handler disables public PHP error display and returns a generic response with a random reference ID. Private logs include exception context and stack traces with function arguments disabled. Basic redaction removes common password, token, authorization, secret, and DSN patterns.

Logging must still be treated as sensitive:

- keep `storage/logs` outside the web root;
- use restrictive permissions;
- configure rotation and retention;
- do not log request bodies, credentials, tokens, personal form content, or database rows;
- investigate failures using the reference ID rather than exposing details to the requester.

If the log directory is not writable, the application continues to return a generic error and does not reveal the filesystem problem publicly.

## HTTP Response Headers

The PHP bootstrap and Apache configuration set a baseline that preserves the current site:

- `Content-Security-Policy` restricts scripts, fonts, images, connections, framing, and form targets to the site;
- `X-Content-Type-Options: nosniff`;
- `X-Frame-Options: SAMEORIGIN` plus CSP `frame-ancestors`;
- `Referrer-Policy: strict-origin-when-cross-origin`;
- restrictive camera, microphone, and geolocation permissions;
- same-origin opener and resource policies;
- HSTS from PHP only for production HTTPS requests.

`style-src 'unsafe-inline'` is currently retained because the existing motion system writes CSS custom properties to element style declarations. Scripts remain restricted to same-origin external files. Revisit this exception if the motion implementation changes; do not add `unsafe-eval`.

Test security-header changes against navigation, portfolio filters, fonts, images, animations, and form controls before production use.

## Consultation Form Requirements for the Next Phase

Before enabling server-side submission:

1. Define a minimal data model and retention/deletion policy.
2. Validate every field on the server and normalize Persian/English input intentionally.
3. Decide whether anonymous submission needs a CSRF token based on the final cookie/session design; CSRF protection is mandatory for authenticated actions.
4. Add rate limiting and a privacy-conscious spam defense.
5. Use prepared SQL statements inside explicit failure handling.
6. Prevent email header injection and use an allow-listed server-side recipient if email is later approved.
7. Keep SMTP, Telegram, and API credentials private and out of Git.
8. Return generic client errors and never reveal provider or SQL responses.

## Pre-Deployment Review

Before every cPanel upload:

1. Inspect the final archive contents, including hidden files.
2. Confirm that only `public/index.php`, `public/.htaccess`, and `public/assets/` map to `public_html`.
3. Confirm that all private directories and `.env` remain outside the web root.
4. Search tracked and packaged files for real credentials and private keys.
5. Run PHP syntax checks on every PHP file.
6. Run the CLI database check only with an approved test or production configuration.
7. Verify generic failure behavior and private log permissions.
8. Review the rendered desktop/mobile site and its existing interactions for regressions.
