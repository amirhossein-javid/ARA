# ARA Studio Agent Guidelines

These instructions apply to the entire repository. Read the relevant source files before changing them. Use [README.md](README.md) for project orientation, [SECURITY.md](SECURITY.md) for mandatory security boundaries, and [DEPLOYMENT.md](DEPLOYMENT.md) for cPanel operations.

## Project context

ARA Studio is a real, publicly deployed Persian RTL digital agency website and administration system, available at `https://ara-studio.ir`. The public experience presents corporate websites, online stores, landing pages, branding and visual identity, UI/UX, and custom digital solutions through a consultation-led flow. The application stores consultation requests and provides authenticated management for consultations, portfolio projects, administrator accounts, public site settings, and homepage SEO.

Treat the live deployment, the repository working tree, and the verification performed for a specific change as separate facts. Do not describe the project as unpublished or as a staging candidate. Do not assume that the current branch is deployed, that production matches the working tree, or that a runtime/security check passed unless there is direct evidence for that claim.

Technology and hosting constraints:

- native PHP 8.2+, strict types, no framework;
- PDO with MySQL/MariaDB and `utf8mb4`;
- server-rendered HTML, CSS, and vanilla JavaScript;
- Apache shared cPanel hosting;
- no Composer or Node runtime/build dependency;
- public files in `public_html`; private application and secrets in sibling `ara-studio`.

## Authoritative paths

- `public/index.php`: front controller and explicit router.
- `bootstrap/app.php`: autoloading, environment/config validation, error handling, security headers, and container assembly.
- `config/*.php`: bounded feature and environment configuration.
- `app/Http/Controllers/`: request orchestration and response selection.
- `app/Auth/`: administrator authentication, access checks, sessions, provisioning, and account lifecycle.
- `app/Consultation/`: public validation, duplicate protection, and public/admin repositories.
- `app/Portfolio/`: portfolio validation, persistence, and image storage.
- `app/Settings/` and `app/Seo/`: allow-listed settings, SEO persistence, public documents, and social images.
- `app/Security/`: CSRF, same-origin, session, and file-backed rate limiting.
- `app/Database/Connection.php`: the only PDO construction pattern.
- `resources/views/`: escaped public and Persian RTL admin templates.
- `public/assets/`: dependency-free frontend code, fonts, logo, and hardened upload directories.
- `database/migrations/`: manual forward-only schema history.
- `bin/`: private CLI-only database and account tools.
- `storage/`: private logs, locks, rate-limit state, and temporary provisioning input.

## Architecture and coding conventions

### Bootstrap and routing

Keep the single-entry architecture. Add or change routes explicitly in `public/index.php`; do not introduce dynamic controller resolution. Preserve positive-integer route identifiers, fixed method dispatch, `404`/`405` behavior, and safe redirect allow lists. Private files must never become directly routable.

The bootstrap must remain dependency-free and fail closed on invalid production configuration. Configuration comes from the private `.env` or real server environment variables; process/server values take precedence. Never read secrets into public JavaScript or views.

### Controllers, services, repositories, and views

- Controllers authenticate, validate request shape/method/origin/CSRF, coordinate collaborators, and choose responses.
- Domain validation belongs in dedicated validators or bounded services where those patterns already exist.
- Repositories own fixed SQL and persistence. Use `Ara\Database\Connection`; use prepared statements for every value and never concatenate request data into SQL structure.
- Views remain simple server-rendered PHP. Escape dynamic HTML text and attributes with `e()`. Use destination-specific encoding for JSON, XML, URLs, JavaScript, CSS, and headers.
- Centralized unexpected errors must remain generic publicly and detailed only in private logs. Do not log credentials, session/CSRF tokens, consultation bodies, contact details, database rows, or uploaded content.

### Existing application patterns

- Consultation submissions are JSON-only, strictly bounded, CSRF/same-origin checked, rate limited, honeypot checked, duplicate guarded, and persisted before success is shown.
- Every protected admin request revalidates the active account and credential/session version. Mutations are POST-only and CSRF protected.
- Account changes require the acting administrator's current password where implemented. Preserve self-deactivation, last-active-admin, and session-invalidation safeguards.
- Portfolio and SEO image code validates actual content, MIME, size and dimensions; creates random filenames; stores relative paths; and deletes only validated paths below the configured directory.
- Site and SEO settings use code-level allow lists. Do not add arbitrary key/value or executable configuration editing.
- The public portfolio renders only published CMS records. When none are published, it must show the production empty state and must not introduce sample or fictional projects.
- `robots.txt`, `sitemap.xml`, metadata, and JSON-LD use validated database settings and verified public contact information only.

### Database changes

Migrations `0000` through `0007` may already be applied. Never edit, reorder, rename, or silently make an applied migration idempotent. Add the next numbered migration, document dependencies and backup/rollback implications, and preserve existing rows. Foreign keys, constraints, timestamps, indexes, and `utf8mb4` conventions should match current schema patterns.

There is no migration ledger or automated down migration. Never run a production migration without explicit permission and a verified backup.

### Frontend and design

Preserve the Persian RTL interface, local Vazirmatn font, responsive layout, existing components, spacing, accessible focus states, and reduced-motion behavior. The established identity is premium minimal black/graphite/silver with the existing ARA logo. Do not introduce a purple theme, replace the logo, or redesign the public site unless explicitly requested.

Keep frontend JavaScript dependency-free, progressively enhanced, and safe when JavaScript fails. Use CSS transitions/transforms for motion, respect `prefers-reduced-motion`, retain keyboard navigation and touch targets, and do not rely on hover alone. Preserve the current consultation form states and public/admin behavior.

## Mandatory security rules

- Require existing authentication/authorization on every protected route.
- Use the existing CSRF and same-origin checks for state-changing requests.
- Validate all input server-side with explicit fields, types, lengths, formats, and request-size limits.
- Use native PDO prepared statements; never compose SQL from untrusted identifiers or fragments.
- Escape stored and request-derived output for its destination context.
- Preserve strict sessions, Secure/HttpOnly/SameSite cookies, expiration, regeneration, and database-backed invalidation.
- Preserve login/API rate limiting and consultation duplicate locking; keep their storage private and concurrency-safe.
- Accept only the existing safe image types and retain MIME/content, size, dimension, filename, path traversal, non-execution, and deletion protections.
- Keep `.env`, configuration, private source, migrations, logs, provisioning files, password hashes, customer data, and keys outside the web root and deployment archive.
- Do not trust forwarding headers for HTTPS unless a separately reviewed trusted-proxy boundary is introduced.
- Keep destructive actions POST-only with explicit confirmation and safe failure behavior.
- Never weaken CSP, HTTPS enforcement, authentication, CSRF, sessions, validation, escaping, uploads, or private-path protections to make a test pass.

## Hosting and packaging

The supported deployment archive has exactly two roots:

```text
public_html/    # generated from public/; web accessible
ara-studio/     # private application, documentation and runtime directories
```

Runtime portfolio and SEO images, `.env`, logs, locks, rate-limit state, provisioning JSON, backups, and Git metadata must not enter the archive. An update must preserve existing files under `public_html/assets/portfolio/uploads/` and `public_html/assets/seo/uploads/`. Follow [DEPLOYMENT.md](DEPLOYMENT.md); do not improvise a repository-root upload.

## Required workflow

1. Read the user request, `git status`, applicable instructions, and only the relevant implementation/docs.
2. Confirm current behavior from source rather than prior conversation or assumptions.
3. Make the smallest maintainable change and reuse existing architecture/security utilities.
4. Avoid unrelated refactoring, broad formatting, or unnecessary full-repository rescans.
5. Preserve backward compatibility unless the request explicitly changes it.
6. For schema changes, add a new migration; never rewrite prior migrations.
7. Run focused checks available in the environment. Do not install tools, containers, frameworks, or services unless explicitly authorized.
8. Distinguish static inspection, syntax checks, simulated checks, real database tests, browser tests, and target-host verification.
9. Report changed files, behavior, tests, unavailable verification, remaining risks, and deployment implications concisely.
10. Ask before work that could alter production data, security boundaries, hosting architecture, external systems, or established product behavior.

## Testing and evidence discipline

- Match verification to the change and use the existing environment before proposing new tooling.
- Report exact commands or checks that ran successfully; do not convert source review into a runtime claim.
- A failed or unavailable local check is a limitation of that verification session, not evidence that the deployed website is unavailable or non-production.
- A successful production smoke test confirms only the exercised behavior on that deployment at that time; it is not a security certification or a guarantee for later changes.
- For deployment work, record the backup boundary, changed destination paths, migration requirement, smoke-test results, and rollback point.

## Non-negotiable constraints

- No Docker, Laravel, Composer, Node runtime, framework, or new infrastructure unless explicitly authorized.
- No public registration, debug/configuration endpoint, automatic web migration, or secret-bearing frontend configuration.
- No exposure of `.env`, credentials, private keys, password hashes, session/CSRF tokens, personal consultation data, or sensitive logs.
- No modification of previously applied migrations.
- No overwrite or deletion of production uploads during deployment.
- No redesign or logo/brand change without explicit authorization.
- No regression to consultation, authentication, portfolio, account, settings, or SEO behavior.
- No claim that a test, database connection, migration, deployment, or production readiness check passed unless it actually ran successfully.
- No commit, push, merge, deployment, production migration, or external side effect without explicit permission.

When requirements conflict or a choice could materially affect production data, security, or architecture, stop and request clarification.
