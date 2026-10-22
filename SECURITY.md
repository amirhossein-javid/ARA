# Security Policy and Backend Baseline

ARA Studio exposes a public landing page with published portfolio records, allow-listed contact settings and managed SEO metadata; dynamic `robots.txt` and `sitemap.xml`; a same-origin consultation endpoint; and protected administration for consultations, portfolio, equal-permission accounts, settings, and SEO. It includes no public request viewer, role system, notification integration, arbitrary settings editor, or general-purpose CMS.

## Reporting a Vulnerability

Report suspected vulnerabilities privately to [info@ara-studio.ir](mailto:info@ara-studio.ir). Do not disclose credentials, personal data, private logs, exploit payloads, or other sensitive security details in public GitHub issues.

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
- `APP_KEY` must be a private random value of at least 32 characters and must not be reused as another credential.
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

## Sessions and CSRF

The public form and administrator actions use a server-side token with constant-time CSRF comparison. Sessions use strict cookie-only mode with `HttpOnly` and `SameSite=Lax`; production administrator routes refuse non-HTTPS requests so their cookies are always `Secure`. Session IDs regenerate after login and logout. Authenticated sessions expire after 30 minutes idle or 8 hours absolute time.

Origin and Referer are checked against `APP_URL` when present, but the CSRF token remains the primary control. Login errors do not disclose whether an account exists. Authentication checks the database on every protected page so inactive accounts lose access on their next request.

HTTPS enforcement and the `Secure` cookie decision use the server's `HTTPS` state or port `443`. Raw forwarding headers such as `X-Forwarded-Proto` are not trusted because they can be client-controlled unless a separately configured trusted proxy removes and rewrites them. The supported cPanel layout terminates TLS at Apache; any future reverse-proxy deployment requires an explicit trusted-proxy configuration before it is used.

Every protected consultation route repeats that access check and sends private `no-store` responses. Search uses fixed SQL structure and prepared values; status values come from a server-side allow list; pagination is bounded; and request identifiers must be positive integers. Stored contact details, messages, and administrator notes are escaped before HTML rendering. Status changes, notes, and permanent deletion are POST-only, same-origin checked, and CSRF protected. Deletion also requires an explicit confirmation field.

Portfolio administration routes use the same controls. Public queries include only `is_published = 1` and use fixed ordering. Project text is escaped for HTML, Telegram URLs are restricted to HTTPS `t.me` post paths and revalidated before public rendering, and database image paths must match the random CMS filename pattern. Draft records are not returned by the public query.

Account and settings mutations are POST-only, same-origin checked, CSRF protected, bounded, and use prepared statements. Sensitive account changes require the acting administrator's current password. A transaction and row locks prevent concurrent requests from disabling the last active administrator; self-deactivation is prohibited. Password and activation-state changes increment `session_version`; its keyed credential fingerprint invalidates all affected sessions, including across deactivate/reactivate cycles. Password hashes are never sent to views or logs.

Site setting names are a code-level allow list; the UI cannot create keys. Public URLs must use HTTPS, Telegram is restricted to a valid `t.me` profile path, and email/optional phone values are format and length checked. Values are escaped for HTML and validated again before public rendering. The settings table contains public presentation data only and must never be used for credentials or executable configuration.

SEO settings use a single allow-listed record rather than arbitrary keys. Canonical URLs must use HTTPS, match the configured website host, and contain no credentials, custom port, query, or fragment. Verification values accept a narrow ASCII token alphabet and are omitted publicly when empty. HTML metadata uses attribute escaping; JSON-LD uses JSON encoding with all HTML-significant characters hex-escaped. Its inline data block receives a per-response CSP nonce, while executable inline scripts remain disallowed.

SEO social images use random filenames and the same content, MIME, size, dimension, path-traversal, and executable-handler protections as portfolio images. Only the built-in logo or a path matching `assets/seo/uploads/<32 lowercase hex>.(jpg|png|webp)` can reach public metadata. Deployment packages exclude runtime SEO images.

## Portfolio Upload Safety

- Accept only JPEG, PNG, and WebP images whose `getimagesize()` type and `fileinfo` MIME agree.
- Enforce the configured 5 MiB file limit, 6000-pixel width/height limits, and 24-million-pixel limit before moving a file.
- Generate a 128-bit random filename and ignore the client filename and extension.
- Store only a relative path in MySQL; resolve filesystem paths from the known public root.
- Delete only paths matching `assets/portfolio/uploads/<32 lowercase hex>.(jpg|png|webp)` and confirm their resolved path remains inside the upload directory.
- Keep upload-directory Apache rules in place so executable extensions are denied and PHP handlers are removed.
- On a database failure, remove a newly stored image. After successful replacement or deletion, clean the prior managed image; an unremovable file may remain as a safe orphan rather than risking arbitrary deletion.
- Keep runtime images out of Git and deployment ZIP files. Back them up separately before cPanel file operations.

## Administrator Provisioning

There is no public registration, setup, password-reset, or recovery endpoint. The two equal-permission accounts are created by a private CLI-only script using a temporary `0600` JSON file outside the web root. Provisioning refuses any non-empty `admins` table, requires exactly two distinct accounts, uses `password_hash(PASSWORD_DEFAULT)`, and deletes the temporary plaintext input after success.

Password rotation is also CLI-only, requires the current password, verifies it with `password_verify()`, and replaces it with a new `password_hash()` result. The password-hash fingerprint stored in each authenticated session causes existing sessions for that administrator to be rejected on their next protected request after rotation. Provisioning and rotation inputs must never be committed, packaged, placed in `public_html`, or included directly in cron commands.

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

`style-src 'unsafe-inline'` is currently retained because the existing motion system writes CSS custom properties to element style declarations. Scripts remain restricted to same-origin external files plus the per-response nonce used only by JSON-LD. Apache does not overwrite the PHP-generated CSP, so the nonce remains effective. Revisit the style exception if the motion implementation changes; do not add `unsafe-eval`.

Test security-header changes against navigation, portfolio filters, fonts, images, animations, and form controls before production use.

## Consultation Submission Controls

The endpoint accepts only a bounded JSON object with an exact field allow list. Server-side validation enforces field types, lengths, UTF-8, contact format, and service values. A visually hidden honeypot rejects basic automation. File-backed rate-limit records and duplicate locks contain only keyed hashes, counters, and timestamps; raw IP addresses and form values are not written to those files.

Rate-limit and duplicate files use `flock` for concurrent request safety. Keyed identities are distributed across a fixed maximum of 256 lock/state shards; expired rate-limit entries are removed while a shard is locked, and each shard has a defensive entry cap. The duplicate check compares validated values only inside a bounded ten-minute database window and executes under a keyed filesystem lock. All inserts and duplicate checks use native PDO prepared statements.

Maintain an approved retention and deletion policy for production consultation data. Dashboard deletion is permanent, so create a verified database backup before bulk deletion and restrict cPanel backup access. SMTP, Telegram, or other delivery integrations require separate security and privacy review before they are introduced.

## Pre-Deployment Review

Before every cPanel upload:

1. Inspect the final archive contents, including hidden files.
2. Confirm that only `public/index.php`, `public/.htaccess`, and `public/assets/` map to `public_html`.
3. Confirm that all private directories and `.env` remain outside the web root.
4. Search tracked and packaged files for real credentials and private keys.
5. Run PHP syntax checks on every PHP file.
6. Inspect the live schema, then import only required, unapplied migrations manually through phpMyAdmin in numeric order. Verify consultation, portfolio, site-setting, session-version, and SEO schemas before using affected dashboard features.
7. Run the CLI database check only with an approved test or production configuration.
8. Verify generic API failure behavior and writable private log, lock, and rate-limit directories.
9. Verify admin HTTPS enforcement, denial of unauthenticated access, mutation CSRF, account lifecycle protections, settings and SEO validation, metadata escaping, JSON-LD/CSP behavior, robots/sitemap output, image type/size/dimension rejection, safe replacement/deletion, portfolio draft exclusion, and destructive confirmations.
10. Confirm no provisioning or rotation JSON remains on the server.
11. Review the rendered desktop/mobile site and its existing interactions for regressions.
