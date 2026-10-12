# ARA Studio Website

ARA Studio is a Persian, right-to-left agency website with a lightweight PHP 8.2+ backend. Phase 2 provides consultation persistence, Phase 3 adds secure authentication, Phase 4A adds consultation management, Phase 4B adds the portfolio CMS, Phase 4C adds administrator lifecycle management and public site settings, and Phase 4D adds secure homepage SEO management. Notifications, role management, and a general-purpose CMS are not implemented.

## Requirements

- PHP 8.2 or newer
- `pdo` and `pdo_mysql` PHP extensions
- PHP `fileinfo` extension for upload MIME verification
- MySQL 8+ or a compatible MariaDB release
- Apache with `mod_headers` recommended for cPanel deployments

No PHP framework, Composer dependency, Node dependency, or build step is required.
Docker and other container tooling are not part of this project. A cPanel deployment requires no local development infrastructure or background service.

## Architecture

```text
app/
  Auth/                       Admin repository, authentication, and session state
  Consultation/               Public validation plus private management repositories
  Portfolio/                  Portfolio persistence, validation, fallback data, and image storage
  Seo/                        SEO validation, persistence, public documents, and image storage
  Settings/                   Allow-listed public settings validation and persistence
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

The homepage reads published portfolio projects from MySQL. Consultation submissions, authenticated dashboard routes, and private CLI database checks also create database connections.

## Local Setup

1. Copy `.env.example` to `.env`.
2. Replace every database placeholder with credentials for a local development database. Keep `.env` outside Git.
3. Set `APP_ENV=local`, `APP_DEBUG=true`, and an accurate local `APP_URL`.
4. Set `APP_KEY` to a private random value of at least 32 characters. Do not reuse a database password or commit this value.
5. Import all unapplied migrations in numeric order and ensure `storage/logs`, `storage/locks`, `storage/rate-limits`, `public/assets/portfolio/uploads`, and `public/assets/seo/uploads` are writable by the PHP process.
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

`database/migrations/0000_database_foundation.sql` establishes the connection conventions. `0001` through `0006` provide consultation, authentication, portfolio, site-setting, and session-version storage. `0007_create_seo_settings.sql` creates one strict homepage SEO record with sensible ARA defaults and no fabricated business information.

For cPanel initialization:

1. Create a database and a dedicated database user in **MySQL Databases**.
2. Grant that user only the privileges needed by the current phase and future approved migrations.
3. Create the private `.env` file with the cPanel-prefixed database and user names.
4. Open phpMyAdmin, select the application database, choose **Import**, and import migrations `0000` through `0007` in numeric order. Existing Phase 4C installations should back up the database and import only `0007_create_seo_settings.sql`.
5. Confirm that `consultation_requests`, `admins`, `portfolio_projects`, `site_settings`, and `seo_settings` exist. Never import migrations automatically from a web request or deployment script.
6. If cPanel Terminal is available, run `php bin/database-check.php` from the private application directory. A successful command is the only acceptable evidence of real connectivity.

## Consultation API

`POST /api/consultation` accepts JSON only, with a maximum body size of 8 KiB. The fields are `name`, `contact`, `service`, `message`, and the empty `website` honeypot. The endpoint applies strict allow-list validation, a session-backed CSRF token, Origin/Referer checks when those headers are present, a keyed per-address file rate limit, and a ten-minute duplicate window.

Successful persistence returns HTTP `201`. Validation, CSRF, duplicate, media-type, request-size, method, and rate-limit failures use appropriate `4xx` responses. Unexpected failures return generic JSON and are logged privately without request bodies or form values.

## Administrator Authentication

The administrator routes are:

- `GET /admin/login` and `POST /admin/login`;
- protected `GET /admin`;
- `POST /admin/logout`.

All administrator accounts have identical permissions because the schema has no role column. Production admin routes require HTTPS. Login and logout use CSRF tokens, same-origin checks, fixed redirects, private no-store responses, prepared SQL, generic credential errors, keyed file rate limits, session-ID regeneration, and server-side expiration. The idle timeout is 30 minutes and the absolute timeout is 8 hours.

HTTPS detection intentionally uses Apache/PHP's trusted `HTTPS` state or server port `443`; client-supplied forwarding headers are not trusted. The documented cPanel layout terminates TLS at Apache. A deployment behind a separate reverse proxy must first configure that proxy and Apache to expose a trusted HTTPS state rather than forwarding arbitrary client headers to PHP.

## Admin Dashboard and Consultation Workflow

The protected dashboard is available at `/admin`. `/admin/consultations` provides fixed-order server-side pagination, allow-listed status filtering, prepared search queries, and links to private request details. The dashboard counts all, new, in-progress, and last-seven-day requests.

The detail page supports the four workflow states `new`, `in_progress`, `completed`, and `archived`, private notes up to 5,000 characters, and confirmed permanent deletion. Status, note, and deletion mutations are POST-only and require the current session's CSRF token. The administrator responsible for the latest status or note update is stored through `last_updated_by_admin_id`; no request content or contact data is written to application logs.

Administrator management is available at `/admin/admins`, public settings at `/admin/settings`, and SEO management at `/admin/seo`.

## Administrator Accounts and Site Settings

Authenticated administrators can list accounts, create another equal-permission administrator, rotate any administrator password, and activate or deactivate accounts. Each sensitive change requires the acting administrator's current password. Self-deactivation and deactivation of the last active administrator are rejected. Password and activation-state changes increment a server-side session version, so all existing sessions for the affected account are rejected even if a deactivated account is later reactivated.

The settings screen edits only `brand_name`, `website_url`, `telegram_url`, `contact_email`, and the optional `contact_phone`. Keys are fixed in application code, values are strictly validated, and prepared upserts store the administrator responsible for the latest update. The public homepage uses these values for organization identity, website host, footer copyright, contact links, and structured data; the browser title is controlled by SEO settings. Settings cannot contain PHP, arbitrary configuration, or secrets.

## SEO Management

`/admin/seo` manages the homepage title and description, same-host HTTPS canonical URL, Open Graph and Twitter/X copy, index/follow directives, optional Google/Bing verification tokens, and one shared social image. The image accepts verified JPEG, PNG, or WebP content up to 5 MiB and is stored under `public/assets/seo/uploads/` with a random filename. Resetting the image restores the existing ARA logo; no remote image URL is accepted.

The homepage emits escaped canonical, robots, Open Graph, Twitter/X, and verification metadata. JSON-LD describes only the verified ARA organization name, website, logo, email, Telegram URL, and optional public telephone. `/robots.txt` excludes `/admin` and `/api/`; `/sitemap.xml` contains only the configured public homepage because the current portfolio has no standalone public project routes. Both documents use the configured public site URL.

## Portfolio CMS

`/admin/portfolio` lists projects with server-side pagination and publication filtering. Administrators can create and edit projects, control display order, independently set an optional Telegram post URL, publish or unpublish, replace or remove the image, and permanently delete a confirmed project. Writes are POST-only and CSRF protected.

The public homepage selects only published records ordered by `display_order` and stable `id`. While the portfolio table has no admin-managed records, the six original cards remain visible as explicitly temporary design samples. Creating the first managed project retires that fallback; if all managed records are drafts, a neutral empty state is shown instead. Drafts are never included in the public query. Optional Telegram links use the public CTA «مشاهده بیشتر» and are omitted when absent.

Images are stored under `public_html/assets/portfolio/uploads/` using random server filenames. JPEG, PNG, and WebP files are accepted up to 5 MiB, 6000×6000 pixels, and 24 million pixels. Both image metadata and `fileinfo` MIME detection must agree. The upload directory's `.htaccess` disables script handlers and direct access to executable extensions.

## Administrator Provisioning

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
      portfolio/uploads/      Runtime portfolio images and Apache upload hardening
      seo/uploads/            Runtime social images and Apache upload hardening
```

The public entry point automatically discovers a private sibling directory named `ara-studio`. If the host requires another location, define the server environment variable `ARA_PRIVATE_PATH` with its absolute path. Never expose that path in client-side code.

Recommended deployment workflow:

1. Build an archive with exactly two top-level folders: `public_html/` from `public/`, and `ara-studio/` containing the private runtime directories listed above.
2. Exclude `.git`, `.env`, logs, database exports, tests, editor files, local artifacts, and previous ZIP files.
3. Upload and extract the archive in the account home directory, not inside `public_html` as a single repository tree.
4. Create `.env` manually in `/home/ACCOUNT/ara-studio/` after extraction. Do not place secrets in the archive.
5. Set private directories to restrictive permissions supported by the host (typically directories `750`, files `640`) and keep `storage/logs`, `storage/locks`, and `storage/rate-limits` writable by PHP. Keep `public_html/assets/portfolio/uploads` writable by PHP and readable by Apache; never grant executable permission to uploaded files. Provisioning JSON must use `0600`.
6. Import all outstanding migrations in order. For a Phase 4C database, import `0007_create_seo_settings.sql` before uploading this code.
7. Select PHP 8.2+ and enable `pdo_mysql` and `fileinfo` in **MultiPHP Manager** or **Select PHP Version**. Set `upload_max_filesize` to at least `5M` and `post_max_size` to at least `6M`.
8. Preserve and back up runtime portfolio and SEO images before replacing or cleaning `public_html`; deployment archives contain upload hardening but no user-uploaded images. Never replace existing `assets/portfolio/uploads/` or `assets/seo/uploads/` contents during deployment.
9. Confirm HTTPS, CSP/JSON-LD, metadata, robots, sitemap, consultation flow, admin login/logout, account lifecycle safeguards, settings rendering, SEO image replacement/reset, portfolio CRUD, draft exclusion, pagination, filters, notes, status changes, and deletion confirmation before changing production traffic.

Do not upload the repository root directly to `public_html`.

## Error Behavior

PHP warnings and exceptions are converted into centralized failures. Browser responses remain generic and contain a random reference ID; stack traces, SQL details, paths, and credentials are not rendered. Detailed diagnostics are written only to `storage/logs/app.log`. Development mode may add a note that a private log exists, but it still does not expose exception details.

Production must use `APP_ENV=production` and `APP_DEBUG=false`. Configure log rotation and retention through cPanel so private diagnostics do not grow indefinitely.

## Next Phase

The recommended next phase should be deliberately selected, such as approved notification delivery or operational reporting. Every future admin action must reuse the existing authentication, no-cache, validation, escaping, prepared-query, upload, and CSRF boundaries.

See [SECURITY.md](SECURITY.md) before adding any public endpoint or authenticated feature.

For the exact fresh-install, update, rollback, permission, provisioning, and runtime smoke-test procedure, follow [DEPLOYMENT.md](DEPLOYMENT.md).
