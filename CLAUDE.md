# Shiplog

A self-hosted work diary that writes itself from your git commits and Claude Code sessions. Started
from [laravel-template](https://github.com/mohamadsyukri/laravel-template). Personal coding style
comes from the `coding-style` skill ([claude-style-kit](https://github.com/mohamadsyukri/claude-style-kit));
this file holds only what this codebase knows.

## Stack
- Laravel 13 (streamlined structure: `bootstrap/app.php`), PHP 8.4, Pest 5, Pint (Laravel preset)
- Blade + Bootstrap 5.3 + vanilla JS/jQuery; yajra DataTables for the admin listings; Select2
- Fortify (sign in, password reset and change — no self-registration), spatie/laravel-permission 8,
  spatie/laravel-activitylog 5
- SQLite by default, with cache, sessions and queue in the database — no Redis needed. MySQL works
  through `.env`.

## Running things (with [docker-webstack](https://github.com/mohamadsyukri/docker-webstack))
- PHP: `docker exec -e XDEBUG_MODE=off -w /var/www/projects/me/shiplog php84 php artisan …`
- Site: http://shiplog.local (`bin/new-site.sh shiplog me/shiplog php84` in the webstack)
- First run: `composer setup` (install, key, migrate, seed, npm build) — a human runs this.
- Front-end: `bin/node.sh me/shiplog npm run build` from the webstack. Build before running tests:
  without `public/build/manifest.json` every page test fails on "Vite manifest not found".
- Tests: `php artisan test --compact` — sqlite in memory, never a real database.
- Browser: `bin/playwright.sh me/shiplog` from the webstack (e2e/ specs, Playwright 1.62).

## How the app is wired
- **Access:** every named route inside the `route.permission` group (routes/web.php) is a
  permission — config/access.php, App\Http\Middleware\AuthorizeRoute. Supporting routes fold into
  their screen: datatable/export/pdf → index, store → create, update → edit. A new route is closed
  until `php artisan permissions:sync` registers it and a role is granted it on Administration >
  Roles. The `System Administrator` role passes every check.
- **Sidebar:** the `menus` table (MenuSeeder), each item shown only when the user may open its
  route (App\Support\Menu\Sidebar).
- **Reference module — Imports** (`imports.*`): copy its shape for new features. Thin
  controller → `final readonly` service (ImportService) → Form Request (StoreImportRequest);
  `index` = paginated history; `create` = the form; `store` redirects with a toast, or back to the
  form with every problem listed. The board (`board.index`) is the read-only pattern: a service
  builds a result object (BoardService → BoardPage) for the view.
- **UI:** components in resources/views/components (`card`, `button`, `table`, `form.*`,
  `breadcrumb`); the one confirm modal via `data-confirm`; toasts from
  `session('success'|'error')` or `window.toast(message, type)`.
- **Strings:** English only. Screen text is written in `__()` as the English sentence itself;
  validation, auth and DataTables wording come from Laravel's and DataTables' own English. There is
  no lang/ folder (the template's Malay was removed when it became Shiplog).
- **Audit:** models opt in with `LogsActivity` + `getActivitylogOptions()` (logOnly, never secrets);
  Administration > Audit Trail shows before/after values.

## Adding a feature
1. Migration (schema only) + model + factory
2. Service + Form Requests + controller in app/Http/Controllers/<Area>/
3. routes/modules/<area>.php, required inside the protected group in routes/web.php
4. Views in resources/views/modules/<area>/ — index, show, create, edit, `_form/`, `_partials/`
5. `php artisan permissions:sync` and a MenuSeeder row
6. Pest tests in tests/Feature/<Area>/: access gate → 404 → happy save → validation →
   transitions → child rows → delete rules → links

## Rules specific to this codebase
- `Model::shouldBeStrict()` is on outside production: eager-load, no unknown attributes.
- Date-only columns cast to `date:Y-m-d`; screens show d/m/Y.
- Seeders are idempotent (DatabaseSeeder is safe to re-run); data never goes in migrations.
- Users are deactivated, never deleted, so the audit trail always has its causer.
- `package-lock.json` is committed, so `npm ci` rebuilds the front end that was tested. This is a
  deliberate exception to the style kit's never-stage rule: a solo public app has no lock-file
  merge conflicts to avoid.
