# ARA Studio cPanel Deployment and Runtime Checklist

This runbook covers a fresh installation and an update of an existing ARA Studio deployment. It does not run migrations or deploy the application automatically.

## 1. Required cPanel Runtime

- PHP 8.2 or newer.
- MySQL 8 or a compatible MariaDB release.
- PHP extensions: `PDO`, `pdo_mysql`, `fileinfo`, `session`, `filter`, and `json`.
- Apache `mod_rewrite`. `mod_headers` is strongly recommended so static responses receive the same baseline headers as PHP responses.
- The domain document root must be `public_html`; the private application must be a sibling directory outside the web root.
- Enable cPanel **Force HTTPS Redirect** for the production domain after its certificate is valid.

Recommended PHP settings:

| Setting | Required value | Verification |
| --- | --- | --- |
| `file_uploads` | `On` | Portfolio and SEO image uploads work. |
| `upload_max_filesize` | at least `5M` | A file below 5 MiB reaches application validation. |
| `post_max_size` | at least `6M` | Multipart forms are not discarded by PHP. |
| `max_file_uploads` | at least `1` | A single image can be uploaded. |
| `display_errors` | `Off` in production | Deliberate failures show a generic response. |
| `expose_php` | `Off` recommended | Responses do not advertise a PHP version. |

Do not use permission `777`. A suitable starting point on typical cPanel hosting is:

- private directories: `750`; private files: `640`;
- private `.env` and temporary provisioning JSON: `600`;
- public directories: `755`; public files and uploaded images: `644`;
- `ara-studio/storage/logs`, `storage/locks`, and `storage/rate-limits`: writable by the PHP account, normally `750`;
- `public_html/assets/portfolio/uploads` and `public_html/assets/seo/uploads`: writable by PHP and readable by Apache, normally `755` for directories and `644` for files.

Hosting ownership models differ. Tighten permissions where possible while confirming that PHP can write only the four runtime directories listed above.

## 2. Verified Package Layout

Extract `ara-studio-cpanel.zip` in the cPanel account home directory, not inside `public_html`. The archive has exactly these top-level directories:

```text
/home/ACCOUNT/
  ara-studio/                 private application
    .env.example
    app/
    bin/
    bootstrap/
    config/
    database/migrations/
    resources/
    storage/
  public_html/                domain document root
    .htaccess
    index.php
    assets/
```

The package contains no real `.env`, Git metadata, logs, provisioning input, rate-limit state, duplicate locks, or runtime portfolio/SEO images. The two upload directories contain only `.htaccess` and `.gitkeep` controls.

After extraction, confirm that visiting `/ara-studio/`, `/.env`, or `/database/migrations/` does not expose a private file. If any private path is web-accessible, fail the deployment and correct the document root before continuing.

## 3. Migration Order and Recovery

Migrations are manual, forward-only SQL files. There is no migration ledger and no down migration. MySQL/MariaDB DDL may auto-commit, so a failed import cannot be assumed to roll back as one transaction.

| Order | Migration | Dependency and effect |
| --- | --- | --- |
| 0000 | `0000_database_foundation.sql` | Sets `utf8mb4` and UTC connection conventions; creates no table. |
| 0001 | `0001_create_consultation_requests.sql` | Creates consultation storage. |
| 0002 | `0002_create_admins.sql` | Creates administrator accounts. |
| 0003 | `0003_extend_consultation_workflow.sql` | Requires 0001 and 0002; adds workflow notes, status constraint, and admin foreign key. |
| 0004 | `0004_create_portfolio_projects.sql` | Creates portfolio storage; inserts no fictional projects. |
| 0005 | `0005_create_site_settings.sql` | Requires 0002; creates allow-listed public settings and seeds approved defaults. |
| 0006 | `0006_add_admin_session_version.sql` | Requires 0002; adds server-side session invalidation. |
| 0007 | `0007_create_seo_settings.sql` | Requires 0002 and is used with 0005; creates the singleton SEO record and approved defaults. |

Before every import:

1. Export the complete database from phpMyAdmin in SQL format, including structure, data, constraints, triggers, and `AUTO_INCREMENT` values.
2. Store the export outside `public_html` and verify that it is non-empty and downloadable.
3. Back up both upload directories separately.
4. Identify the last applied migration by inspecting tables and columns in phpMyAdmin. Do not infer it only from filenames on the server.
5. Import only unapplied migrations, one file at a time, in numeric order. Never rerun a `CREATE TABLE` or `ALTER TABLE` migration against an already migrated schema.
6. Stop on the first error and preserve the error text privately. Do not continue with later migrations.

Rollback means restoring the pre-deployment database export and the matching upload backup, then restoring the previous code package. Manually dropping columns or constraints is not an approved rollback procedure.

## 4. Fresh Installation

1. In cPanel **Domains**, point the production domain to `/home/ACCOUNT/public_html` and install a valid TLS certificate.
2. In **MySQL Databases**, create a new database and a dedicated application user. Assign the user to that database. Use the cPanel-prefixed names exactly as displayed.
3. Import migrations `0000` through `0007` in order with phpMyAdmin.
4. Extract the ZIP in `/home/ACCOUNT/`. Confirm that the archive created sibling `ara-studio` and `public_html` directories.
5. Copy `ara-studio/.env.example` to `ara-studio/.env`; keep `.env` outside `public_html` and set permission `600`.
6. Configure private values without sending them through chat, tickets, source control, or cron command arguments:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://ara-studio.ir
   APP_TIMEZONE=Asia/Tehran
   APP_KEY=<private random value of at least 32 characters>
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=<cPanel database name>
   DB_USERNAME=<cPanel database user>
   DB_PASSWORD=<private database password>
   DB_CHARSET=utf8mb4
   ```

7. `APP_URL` must be the exact public HTTPS origin used by visitors. Choose either the `www` or non-`www` host, configure the other host to redirect to it, and use the same origin in site/SEO settings.
8. Apply the permissions from section 1 and verify that private storage and public upload directories are writable.
9. If cPanel Terminal is available, run the private connectivity check from `/home/ACCOUNT/ara-studio`:

   ```text
   /usr/local/bin/php -q bin/database-check.php
   ```

   The PHP CLI path varies by host. Pass only if the script prints `Database connection succeeded.` Do not treat a working homepage as proof of database connectivity.
10. Provision the first two administrators using section 6, then enable Force HTTPS Redirect and run all smoke tests.

## 5. Updating an Existing Deployment

1. Record the current code version and take verified database and upload backups.
2. Inspect the existing schema and determine the exact unapplied migrations. For a complete Phase 4C installation, only `0007_create_seo_settings.sql` is expected; do not assume this without checking.
3. Import unapplied migrations before switching to code that requires them. Existing older code ignores the new Phase 5 tables/columns.
4. Extract the new archive into a temporary account-home directory and inspect it before copying files.
5. Update `/home/ACCOUNT/ara-studio` while preserving the existing `.env`, logs, locks, rate-limit state, and `storage/provision` contents.
6. Update public assets and `.htaccess`, then replace `public_html/index.php` last to keep the mixed-version interval short.
7. Never delete or replace these directories as a unit:

   ```text
   public_html/assets/portfolio/uploads/
   public_html/assets/seo/uploads/
   ```

   Copy only the packaged `.htaccess` controls into them. Existing random image files must remain in place.
8. Reapply expected permissions, run the database check, and complete the smoke tests before considering the update successful.
9. If a blocking failure occurs, stop traffic changes and perform the full rollback described in section 3. Do not keep a partially migrated database with older code unless that exact combination has been verified.

## 6. Provisioning the First Two Administrators

Provisioning is CLI-only and refuses to run if any administrator already exists.

1. In cPanel File Manager, create `/home/ACCOUNT/ara-studio/storage/provision/admins.json` with permission `0600` and exactly two unique lowercase usernames. Enter credentials directly in cPanel; never put them in the deployment ZIP or Git.
2. Use the documented JSON structure from `README.md`. Each password must contain at least 14 characters and must not equal its username.
3. When SSH/Terminal is unavailable, create a temporary cPanel Cron Job:

   ```text
   /usr/local/bin/php -q /home/ACCOUNT/ara-studio/bin/provision-admins.php
   ```

4. Let it run once, remove the cron job immediately, and verify the exit result:
   - exit `0`: exactly two accounts were created and the input file was deleted;
   - exit `1`: provisioning failed and no success should be assumed;
   - exit `2`: accounts were created, but the now-cleared input file must be deleted manually.
5. Confirm in phpMyAdmin that exactly two active rows exist and that `password_hash` values are hashes, not the supplied passwords. Never copy hashes into a report.

Do not rerun provisioning for account changes. Use the authenticated admin account-management page or the documented private password-rotation script.

## 7. HTTPS, Cookies, and Apache Verification

Pass all of the following:

- `http://ara-studio.ir/...` redirects to the exact HTTPS origin at the hosting layer.
- `https://ara-studio.ir/admin/login` loads, while the same admin route over direct HTTP is rejected if a redirect is bypassed.
- The login response sets `ARA_SESSION` with `Secure`, `HttpOnly`, `SameSite=Lax`, and path `/`.
- `APP_URL` exactly matches the browser origin; otherwise Origin/Referer checks will reject valid forms.
- `/admin/login`, `/robots.txt`, `/sitemap.xml`, and a nested admin route resolve through Apache rewrite rules rather than returning a server-level 404.
- A nonexistent route returns the application's `404` response.
- Responses contain `X-Content-Type-Options`, `X-Frame-Options`, Referrer/Permissions policies, and the PHP-generated CSP. Production HTTPS responses contain HSTS.
- Files under both upload directories cannot execute as PHP, directory listings are disabled, and a direct request for an executable-extension test file is denied. Do not place a real script in production to test this; use a harmless text fixture in staging and remove it afterwards.

The application deliberately does not trust raw `X-Forwarded-Proto`. A reverse proxy or CDN must be configured to provide a trusted Apache/PHP HTTPS state before production use.

## 8. Smoke-Test Matrix

Run with test-only content that can be removed afterward. Record HTTP status, expected UI result, and relevant database change without copying personal data or secrets into the test report.

| Area | Test | Pass | Fail / safe next action |
| --- | --- | --- | --- |
| Homepage | Load `/` at desktop and mobile widths; inspect network/console. | HTTP 200; CSS, JS, font and logo load; no console error or horizontal overflow. | Stop; check document root, asset permissions, CSP and paths. |
| Consultation | Submit one valid form, then inspect `consultation_requests`. | UI shows Persian success only after HTTP 201; exactly one matching row exists with status `new`. | Preserve entered data; inspect private log by reference ID and DB/storage permissions. |
| Consultation abuse | Resubmit the same payload and submit malformed/oversized requests. | Duplicate returns 409; invalid input/content type/size returns the documented 4xx; no SQL detail leaks. | Disable public form traffic until validation and limits are corrected. |
| Admin auth | Try a wrong password, then valid login and logout. | Error is generic; valid login regenerates session and reaches `/admin`; logout is POST and protected page redirects to login. | Check HTTPS, `APP_URL`, cookie attributes, CSRF, DB and rate-limit directory. |
| Session lifecycle | Leave a staging session idle for the configured 30 minutes; rotate its password from the other admin. | Idle session expires; password change invalidates the affected existing session on its next request. | Do not launch admin access until server time, sessions and `session_version` are verified. |
| Consultations admin | Find the public test submission; update status and notes; delete only the test record. | Pagination/detail work; escaped content renders as text; timestamps/admin attribution update; confirmed deletion removes one row. | Restore only from backup if real data was affected; inspect CSRF and prepared-query path. |
| Portfolio | Create a draft with valid JPEG/PNG/WebP, publish, reorder, replace image, unpublish and delete. | Draft is absent publicly; published item appears in stable order; Telegram CTA appears only for a valid link; old managed image is removed or remains only as a harmless orphan. | Unpublish the item; check `fileinfo`, PHP size limits, upload permissions and `.htaccess`. |
| Upload rejection | Try wrong extension/content, over-limit size/dimensions and malformed image in staging. | Each is rejected; no database path and no executable file remains. | Disable uploads and inspect MIME/dimension validation and host limits. |
| Admin accounts | In staging, create an account, rotate its password, test self-disable and last-active protection. | Hash is stored; old sessions are invalidated; self-disable and disabling the last active admin are rejected. | Restore account state through a verified active admin or private rotation process; never edit hashes manually. |
| Site settings | Change one public value to approved test data, verify homepage, then restore it. | Footer/contact/brand output changes safely and no HTML executes. | Restore prior settings; inspect allow-list validation and escaping. |
| SEO | Change test metadata/image, view source, then restore. | Escaped title/meta/OG/Twitter tags and nonce-protected JSON-LD match settings; canonical is absolute HTTPS. | Restore defaults; inspect validation, CSP nonce and image path. |
| Robots | Request `/robots.txt`. | Text response references the configured HTTPS sitemap and excludes `/admin` and `/api/`. | Check rewrite, database SEO/settings rows and `APP_URL`. |
| Sitemap | Request `/sitemap.xml` and validate as XML. | Contains only the configured canonical homepage when indexing is enabled; contains no admin/API route. | Correct canonical/index settings before search-engine submission. |

## 9. Troubleshooting Boundaries

- Use the response reference ID to locate the matching entry in private `storage/logs/app.log`; never expose that log publicly.
- A generic 500 usually means missing `.env`, invalid `APP_KEY`, unavailable DB, missing migration, or unwritable private storage. Check those in that order.
- A 403 on valid forms usually indicates an `APP_URL`/browser-origin mismatch, stale CSRF session, or missing cookie.
- A 404 on all nested routes indicates missing `mod_rewrite`, ignored `.htaccess`, or the wrong document root.
- Empty `$_POST` on image forms usually indicates `post_max_size` is lower than the request. Increase it only to the documented minimum; application limits still apply.
- Upload errors require checking `fileinfo`, directory ownership/permissions and the PHP upload limits. Do not weaken filename, MIME or path checks.
- Never enable `APP_DEBUG` in production, move `.env` into `public_html`, grant `777`, disable CSRF, expose a diagnostic PHP page, or rerun migrations to troubleshoot.

Deployment is complete only after every applicable smoke test passes on the target cPanel environment and the verified backups remain recoverable.
