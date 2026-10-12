# ARA Studio Website

ARA Studio is a Persian, right-to-left agency website with a lightweight PHP 8.2+ backend. The existing public interface remains unchanged. Phase 2 provides consultation persistence, Phase 3 adds secure authentication for exactly two equal-permission administrators, and Phase 4A adds the first functional consultation dashboard. Portfolio, SEO, settings, notifications, role management, and CMS features are not implemented.

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
  Auth/                       Admin repository, authentication, and session state
  Consultation/               Public validation plus private management repositories
  Config/Environment.php       Minimal environment-file loader
  Database/Connection.php      Reusable PDO connection factory
  Http/Controllers/            Consultation and admin request handling
  Http/                        JSON request/response and centralized safe errors
  Http/SecurityHeaders.php     Application-level response headers
  Security/                    Session CSRF, same-origin, and file rate limiting
  Support/helpers.php          Context-aware HTML escaping helper
bin/
  database-check.php           Private CLI-only database connectivity check
  provision-admins.php         Private one-time two-account provisioning
  rotate-admin-password.php    Private credential rotation
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
resources/views/admin/         Login, dashboard, request list, and request detail views
storage/logs/                  Private runtime logs; log files are ignored
storage/locks/                 Private duplicate-submission locks
storage/rate-limits/           Private concurrent rate-limit state
storage/provision/             Temporary private provisioning input
```

The homepage does not connect to MySQL. A connection is created for consultation API requests, authenticated dashboard routes, or private CLI database checks.

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

`database/migrations/0000_database_foundation.sql` establishes the connection conventions. `0001_create_consultation_requests.sql` creates the Phase 2 consultation table. `0002_create_admins.sql` creates the two-account authentication table without roles or personal profile fields. `0003_extend_consultation_workflow.sql` preserves existing requests while adding private notes, the administrator update reference, workflow indexes, and the allowed-status constraint.

For cPanel initialization:

1. Create a database and a dedicated database user in **MySQL Databases**.
2. Grant that user only the privileges needed by the current phase and future approved migrations.
3. Create the private `.env` file with the cPanel-prefixed database and user names.
4. Open phpMyAdmin, select the application database, choose **Import**, and import `0000_database_foundation.sql`, `0001_create_consultation_requests.sql`, `0002_create_admins.sql`, and `0003_extend_consultation_workflow.sql` in that order. Existing installations should import only the new, unapplied migration after taking a database backup.
5. Confirm that `consultation_requests` contains `admin_notes` and `last_updated_by_admin_id`, and that `admins` exists. Never import migrations automatically from a web request or deployment script.
6. If cPanel Terminal is available, run `php bin/database-check.php` from the private application directory. A successful command is the only acceptable evidence of real connectivity.

## Consultation API

`POST /api/consultation` accepts JSON only, with a maximum body size of 8 KiB. The fields are `name`, `contact`, `service`, `message`, and the empty `website` honeypot. The endpoint applies strict allow-list validation, a session-backed CSRF token, Origin/Referer checks when those headers are present, a keyed per-address file rate limit, and a ten-minute duplicate window.

Successful persistence returns HTTP `201`. Validation, CSRF, duplicate, media-type, request-size, method, and rate-limit failures use appropriate `4xx` responses. Unexpected failures return generic JSON and are logged privately without request bodies or form values.

## Administrator Authentication

The administrator routes are:

- `GET /admin/login` and `POST /admin/login`;
- protected `GET /admin`;
- `POST /admin/logout`.

Both accounts have identical permissions because the schema has no role column. Production admin routes require HTTPS. Login and logout use CSRF tokens, same-origin checks, fixed redirects, private no-store responses, prepared SQL, generic credential errors, keyed file rate limits, session-ID regeneration, and server-side expiration. The idle timeout is 30 minutes and the absolute timeout is 8 hours.

## Admin Dashboard and Consultation Workflow

The protected dashboard is available at `/admin`. `/admin/consultations` provides fixed-order server-side pagination, allow-listed status filtering, prepared search queries, and links to private request details. The dashboard counts all, new, in-progress, and last-seven-day requests.

The detail page supports the four workflow states `new`, `in_progress`, `completed`, and `archived`, private notes up to 5,000 characters, and confirmed permanent deletion. Status, note, and deletion mutations are POST-only and require the current session's CSRF token. The administrator responsible for the latest status or note update is stored through `last_updated_by_admin_id`; no request content or contact data is written to application logs.

Portfolio, administrator management, SEO, and settings appear only as disabled navigation labels for orientation. They have no routes or simulated actions in Phase 4A.

### One-time provisioning without SSH

Provisioning is private and CLI-only; there is no registration or browser setup endpoint.

1. Import `0002_create_admins.sql` through phpMyAdmin.
2. In cPanel File Manager, create `/home/ACCOUNT/ara-studio/storage/provision/admins.json` outside `public_html` with exactly this structure and two unique lowercase usernames:

   ```json
   {
     "admins": [
       {"username": "replace_admin_one", "password": "replace-with-a-long-unique-password"},
       {"username": "replace_admin_two", "password": "replace-with-another-long-unique-password"}
     ]
   }
   ```

3. Set the file permission to `0600`.
4. In cPanel **Cron Jobs**, temporarily schedule this command using the PHP 8.2+ CLI path supplied by the host:

   ```text
   /usr/local/bin/php -q /home/ACCOUNT/ara-studio/bin/provision-admins.php
   ```

5. Let it run once, review the generic success result, and immediately remove the cron job. The script refuses to run when any administrator already exists, creates exactly two accounts in one transaction, hashes passwords with `password_hash()`, and clears/deletes `admins.json` after success.
6. If the script reports exit code `2`, the accounts were created but cPanel prevented automatic deletion; immediately delete the emptied provisioning file manually.

Never place provisioning JSON in Git, the deployment ZIP, `public_html`, a cron command, or cron email output.

### Password rotation

Create private `storage/provision/password-rotation.json` with permission `0600`:

```json
{
  "username": "replace_admin_username",
  "current_password": "replace-current-password",
  "new_password": "replace-new-long-unique-password"
}
```

Run `/usr/local/bin/php -q /home/ACCOUNT/ara-studio/bin/rotate-admin-password.php` once through a temporary cPanel Cron Job, then remove the job. Rotation requires the active account's current password, uses `password_verify()` and `password_hash()`, and deletes the private input after success. Existing sessions for that account are rejected on their next protected request.

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
5. Set private directories to restrictive permissions supported by the host (typically directories `750`, files `640`) and keep `storage/logs`, `storage/locks`, and `storage/rate-limits` writable by PHP. Provisioning JSON must use `0600`.
6. Select PHP 8.2+ and enable `pdo_mysql` in **MultiPHP Manager** or **Select PHP Version**.
7. Confirm HTTPS, response headers, the homepage, consultation flow, admin login/logout, expiration behavior, unauthenticated redirects, pagination, filters, notes, status changes, and deletion confirmation before changing DNS or production traffic.

Do not upload the repository root directly to `public_html`.

## Error Behavior

PHP warnings and exceptions are converted into centralized failures. Browser responses remain generic and contain a random reference ID; stack traces, SQL details, paths, and credentials are not rendered. Detailed diagnostics are written only to `storage/logs/app.log`. Development mode may add a note that a private log exists, but it still does not expose exception details.

Production must use `APP_ENV=production` and `APP_DEBUG=false`. Configure log rotation and retention through cPanel so private diagnostics do not grow indefinitely.

## Next Phase

The recommended next phase is one deliberately selected dashboard module, such as portfolio management. Every future admin action must reuse the existing authentication, no-cache, validation, escaping, prepared-query, and CSRF boundaries. Email or Telegram delivery should be added only after the stored submission flow is verified on hosting and provider secrets management is approved.

See [SECURITY.md](SECURITY.md) before adding any public endpoint or authenticated feature.
