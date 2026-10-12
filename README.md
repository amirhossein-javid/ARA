# ARA Studio Website

ARA Studio is a Persian, right-to-left agency website with a lightweight PHP 8.2+ backend. The existing HTML, CSS, Vanilla JavaScript, Vazirmatn font, motion, portfolio filters, and navigation are preserved. Phase 2 adds a same-origin consultation submission API and MySQL persistence; it does not add an admin panel, authentication, notifications, or a CMS.

## Requirements

- PHP 8.2 or newer
- `pdo` and `pdo_mysql` PHP extensions
- MySQL 8+ or a compatible MariaDB release
- Apache with `mod_headers` recommended for cPanel deployments

No PHP framework, Composer dependency, Node dependency, or build step is required.
Docker and other container tooling are not part of this project. A cPanel deployment requires no local development infrastructure or background service.

## Architecture

```text
app/
  Consultation/               Validation, duplicate guard, and PDO repository
  Config/Environment.php       Minimal environment-file loader
  Database/Connection.php      Reusable PDO connection factory
  Http/Controllers/            Consultation API request handling
  Http/                        JSON request/response and centralized safe errors
  Http/SecurityHeaders.php     Application-level response headers
  Security/                    Session CSRF, same-origin, and file rate limiting
  Support/helpers.php          Context-aware HTML escaping helper
bin/
  database-check.php           Private CLI-only database connectivity check
bootstrap/app.php              Autoloading, configuration, validation, and startup
config/
  app.php                      Application environment configuration
  database.php                 Database configuration mapping
database/migrations/           Versioned SQL migration foundation
public/
  index.php                    Only public PHP entry point
  .htaccess                    Apache hardening and directory settings
  assets/                      Existing frontend assets
resources/views/home.php       Existing landing-page markup
storage/logs/                  Private runtime logs; log files are ignored
storage/locks/                 Private duplicate-submission locks
storage/rate-limits/           Private concurrent rate-limit state
```

The homepage does not connect to MySQL. A connection is created only for a consultation API request or the private CLI connectivity check.

## Local Setup

1. Copy `.env.example` to `.env`.
2. Replace every database placeholder with credentials for a local development database. Keep `.env` outside Git.
3. Set `APP_ENV=local`, `APP_DEBUG=true`, and an accurate local `APP_URL`.
4. Set `APP_KEY` to a private random value of at least 32 characters. Do not reuse a database password or commit this value.
5. Ensure `storage/logs`, `storage/locks`, and `storage/rate-limits` are writable by the PHP process.
6. Start the site from the public document root:

   ```bash
   php -S 127.0.0.1:8080 -t public
   ```

7. Open `http://127.0.0.1:8080`.

To check real database connectivity from a terminal, run:

```bash
php bin/database-check.php
```

The command intentionally returns only a success message or a generic error reference. Database failures are recorded in `storage/logs/app.log`; credentials are not printed to the terminal or browser.

## Configuration

Configuration can come from real process/server environment variables or the private `.env` file. Existing process variables take precedence. Supported values are documented in `.env.example`:

- `APP_ENV`: `local`, `development`, `testing`, or `production`
- `APP_DEBUG`: must be `false` in production
- `APP_URL`: absolute HTTP(S) origin
- `APP_TIMEZONE`: valid PHP timezone identifier
- `APP_KEY`: private random value with at least 32 characters, used only for keyed server-side hashes
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `DB_CHARSET`: fixed to `utf8mb4`

Do not place `.env` in `public/` or `public_html`, and do not add real credentials to `.env.example`.

## Database Foundation

`Ara\Database\Connection` uses PDO with:

- exception-based error handling;
- native prepared statements (`ATTR_EMULATE_PREPARES=false`);
- associative fetches;
- `utf8mb4` in the DSN;
- non-persistent connections.

`database/migrations/0000_database_foundation.sql` establishes the connection conventions. `database/migrations/0001_create_consultation_requests.sql` creates the Phase 2 table with the existing form fields, a `new` status, UTC application timestamps, and indexes for status and recent-request checks.

For cPanel initialization:

1. Create a database and a dedicated database user in **MySQL Databases**.
2. Grant that user only the privileges needed by the current phase and future approved migrations.
3. Create the private `.env` file with the cPanel-prefixed database and user names.
4. Open phpMyAdmin, select the application database, choose **Import**, and import `0000_database_foundation.sql` followed by `0001_create_consultation_requests.sql`.
5. Confirm that `consultation_requests` exists and that its collation is `utf8mb4_unicode_ci`. Never import migrations automatically from a web request or deployment script.
6. If cPanel Terminal is available, run `php bin/database-check.php` from the private application directory. A successful command is the only acceptable evidence of real connectivity.

## Consultation API

`POST /api/consultation` accepts JSON only, with a maximum body size of 8 KiB. The fields are `name`, `contact`, `service`, `message`, and the empty `website` honeypot. The endpoint applies strict allow-list validation, a session-backed CSRF token, Origin/Referer checks when those headers are present, a keyed per-address file rate limit, and a ten-minute duplicate window.

Successful persistence returns HTTP `201`. Validation, CSRF, duplicate, media-type, request-size, method, and rate-limit failures use appropriate `4xx` responses. Unexpected failures return generic JSON and are logged privately without request bodies or form values.

## Secure cPanel Layout

Use separate public and private directories:

```text
/home/ACCOUNT/
  ara-studio/                 Private; not web-accessible
    .env
    app/
    bin/
    bootstrap/
    config/
    database/
    resources/
    storage/
  public_html/                Domain document root
    .htaccess
    index.php
    assets/
```

The public entry point automatically discovers a private sibling directory named `ara-studio`. If the host requires another location, define the server environment variable `ARA_PRIVATE_PATH` with its absolute path. Never expose that path in client-side code.

Recommended deployment workflow:

1. Build an archive with exactly two top-level folders: `public_html/` from `public/`, and `ara-studio/` containing the private runtime directories listed above.
2. Exclude `.git`, `.env`, logs, database exports, tests, editor files, local artifacts, and previous ZIP files.
3. Upload and extract the archive in the account home directory, not inside `public_html` as a single repository tree.
4. Create `.env` manually in `/home/ACCOUNT/ara-studio/` after extraction. Do not place secrets in the archive.
5. Set private directories to restrictive permissions supported by the host (typically directories `750`, files `640`) and keep `storage/logs`, `storage/locks`, and `storage/rate-limits` writable by PHP.
6. Select PHP 8.2+ and enable `pdo_mysql` in **MultiPHP Manager** or **Select PHP Version**.
7. Confirm HTTPS, response headers, the homepage, navigation, filters, animations, and consultation UI before changing DNS or production traffic.

Do not upload the repository root directly to `public_html`.

## Error Behavior

PHP warnings and exceptions are converted into centralized failures. Browser responses remain generic and contain a random reference ID; stack traces, SQL details, paths, and credentials are not rendered. Detailed diagnostics are written only to `storage/logs/app.log`. Development mode may add a note that a private log exists, but it still does not expose exception details.

Production must use `APP_ENV=production` and `APP_DEBUG=false`. Configure log rotation and retention through cPanel so private diagnostics do not grow indefinitely.

## Next Phase

The recommended next phase is a protected administrative workflow for reviewing and updating consultation status. Authentication, authorization, session expiration, audit events, and CSRF protection must be designed before exposing any administrative route. Email or Telegram delivery should be added only after the stored submission flow is verified on hosting and provider secrets management is approved.

See [SECURITY.md](SECURITY.md) before adding any public endpoint or authenticated feature.
