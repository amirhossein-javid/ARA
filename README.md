# ARA Studio Website

ARA Studio is a Persian, right-to-left agency website with a lightweight PHP 8.2+ foundation. The existing HTML, CSS, Vanilla JavaScript, Vazirmatn font, motion, portfolio filters, navigation, and consultation UI are preserved. Phase 1 adds configuration, database, error-handling, and deployment infrastructure only; it does not add a form endpoint, admin panel, authentication, notifications, or a CMS.

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
  Config/Environment.php       Minimal environment-file loader
  Database/Connection.php      Reusable PDO connection factory
  Http/ErrorHandler.php        Central exception/error logging and safe responses
  Http/SecurityHeaders.php     Application-level response headers
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
```

The application does not connect to MySQL during a normal page request. `Ara\Database\Connection` creates a PDO connection only when a later feature or the private CLI check requests one.

## Local Setup

1. Copy `.env.example` to `.env`.
2. Replace every database placeholder with credentials for a local development database. Keep `.env` outside Git.
3. Set `APP_ENV=local`, `APP_DEBUG=true`, and an accurate local `APP_URL`.
4. Ensure `storage/logs` is writable by the PHP process.
5. Start the site from the public document root:

   ```bash
   php -S 127.0.0.1:8080 -t public
   ```

6. Open `http://127.0.0.1:8080`.

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

Phase 1 creates no application tables. `database/migrations/0000_database_foundation.sql` establishes session charset/collation and UTC behavior while reserving the numbered-migration convention for later phases.

For cPanel initialization:

1. Create a database and a dedicated database user in **MySQL Databases**.
2. Grant that user only the privileges needed by the current phase and future approved migrations.
3. Create the private `.env` file with the cPanel-prefixed database and user names.
4. Import numbered migration files in order through phpMyAdmin, or use the MySQL CLI if SSH is available.
5. If cPanel Terminal is available, run `php bin/database-check.php` from the private application directory. A successful command is the only acceptable evidence of real connectivity.

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
5. Set private directories to restrictive permissions supported by the host (typically directories `750`, files `640`) and keep `storage/logs` writable by PHP.
6. Select PHP 8.2+ and enable `pdo_mysql` in **MultiPHP Manager** or **Select PHP Version**.
7. Confirm HTTPS, response headers, the homepage, navigation, filters, animations, and consultation UI before changing DNS or production traffic.

Do not upload the repository root directly to `public_html`.

## Error Behavior

PHP warnings and exceptions are converted into centralized failures. Browser responses remain generic and contain a random reference ID; stack traces, SQL details, paths, and credentials are not rendered. Detailed diagnostics are written only to `storage/logs/app.log`. Development mode may add a note that a private log exists, but it still does not expose exception details.

Production must use `APP_ENV=production` and `APP_DEBUG=false`. Configure log rotation and retention through cPanel so private diagnostics do not grow indefinitely.

## Next Phase

The recommended next phase is a consultation submission service with explicit server-side validation, request-size limits, rate limiting, CSRF decisions based on the chosen session model, spam controls, prepared inserts, and a privacy-conscious retention policy. Email or Telegram delivery should be added only after the submission path is validated and secrets management is confirmed.

See [SECURITY.md](SECURITY.md) before adding any public endpoint or authenticated feature.
