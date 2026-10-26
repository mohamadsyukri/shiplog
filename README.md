# Laravel Template

A Laravel 13 starter for admin systems, so a new project starts with the plumbing done and
features can be built from day one.

- **Bootstrap 5.3 admin layout** — sidebar, top bar, breadcrumbs, cards, form components, one
  confirm dialog, toasts. Screens in Malay (English fallback); code in English.
- **Sign in** with Fortify — password reset and change, inactive accounts blocked, no
  self-registration.
- **Access by route name** — every screen is a permission, granted to roles; the sidebar shows only
  what a user may open. One command registers new routes.
- **User and role screens** — create users, assign roles, tick permissions per screen.
- **Audit trail** — who changed what, with before/after values.
- **Excel and PDF export** — from the same query as the listing, with its filters.
- **A reference CRUD module** ("Pengumuman") showing every convention in working, tested code:
  yajra listing, view-only page, shared create/edit form, draft → publish, a Livewire tab.

Laravel 13 · PHP 8.4 · Livewire 4 · Bootstrap 5.3 · yajra DataTables 13 · spatie/laravel-permission 8 ·
spatie/laravel-activitylog 5 · Laravel Excel 4 · laravel-snappy · Pest 5

## Start a new project

1. On GitHub, click **Use this template** (or clone it and delete `.git`).
2. Put the project where your web server can serve it. With
   [docker-webstack](https://github.com/mohamadsyukri/docker-webstack):

       cd ~/docker-webstack/projects && git clone <your-new-repo> acme/myapp
       ~/docker-webstack/bin/new-site.sh myapp acme/myapp php84

3. Copy `.env.example` to `.env` and set `APP_NAME`, `APP_URL`, the database name, and
   `ADMIN_EMAIL` / `ADMIN_PASSWORD` for the first administrator. Create the database.
4. Install, migrate, seed and build:

       composer setup

   (with the webstack, run it inside the PHP container:
   `docker exec -w /var/www/projects/acme/myapp php84 composer setup`)
5. Sign in at http://myapp.local as the administrator. If `ADMIN_PASSWORD` was empty, the seeder
   printed a random password — change it under Profil.
6. Rename things: `APP_NAME`, the project name in `composer.json` and `CLAUDE.md`, and delete the
   Pengumuman module once your first real module follows its shape.

## Add a feature

1. Migration, model and factory.
2. A `final readonly` service, Form Requests and a thin controller in `app/Http/Controllers/<Area>/`.
3. Routes in `routes/modules/<area>.php`, required inside the protected group in `routes/web.php`.
4. Views in `resources/views/modules/<area>/` — `index`, `show`, `create`, `edit`, `_form/`.
5. `php artisan permissions:sync`, a row in `MenuSeeder`, Malay text in `lang/ms.json`.
6. Pest tests in `tests/Feature/<Area>/`.

The Pengumuman module (`app/Http/Controllers/Announcements`, `app/Services/Announcements`,
`resources/views/modules/announcements`, `tests/Feature/Announcements`) is the example for each step.

## How access works

Every named route inside the `route.permission` middleware group is a permission with the same
name. Supporting routes share their screen's permission — `datatable`, `export` and `pdf` use
`index`; `store` uses `create`; `update` uses `edit` — so a listing never breaks because its data
feed wasn't granted. Settings live in `config/access.php`.

    php artisan permissions:sync          # register permissions for new routes
    php artisan permissions:sync --prune  # also delete permissions whose route is gone

Grant them on **Pentadbiran → Peranan**. The `Pentadbir Sistem` role passes every check. Sidebar
items come from the `menus` table and appear only for users who may open them.

## Commands

| Do | Command |
|---|---|
| Run the tests | `php artisan test --compact` (sqlite in memory) |
| Format PHP | `vendor/bin/pint` |
| Build assets | `npm run build` (or `npm run dev` while working) |
| Browser smoke test | `npx playwright test`, or `bin/playwright.sh <path>` from the webstack |
| Re-seed reference data | `php artisan db:seed` (idempotent — safe to re-run) |

## Import file format

Shiplog reads one JSON file per update, written by the Claude Code skill or by any collector you
write yourself. This example is the importer's test fixture, so it always matches what the
importer accepts (`shiplog/v1`):

```json
{
    "format": "shiplog/v1",
    "range": { "from": "2026-10-20", "to": "2026-10-21" },
    "lanes": [
        { "key": "client-portal", "name": "Client portal", "color": "#2563eb", "order": 1 },
        { "key": "side-project", "name": "Side project", "color": "#16a34a", "order": 2 },
        { "key": "other", "name": "Other", "color": "#6b7280", "order": 3 }
    ],
    "entries": [
        {
            "date": "2026-10-20",
            "lane": "client-portal",
            "summary": "Fixed the invoice total that counted the delivery fee twice on split orders, with a test for the split case.",
            "sources": [
                { "type": "commit", "ref": "3f9c2ab", "label": "Orders (Checkout): stop charging the delivery fee twice on split orders", "url": null },
                { "type": "session", "ref": "b2e41d07-5c1a-4f0e-9a43-7d2f8e6c1b90", "label": "Trace the double delivery fee", "url": null }
            ]
        },
        {
            "date": "2026-10-21",
            "lane": "side-project",
            "summary": "Sketched the weekly summary screen and wrote down the open questions.",
            "sources": [
                { "type": "artifact", "ref": "weekly-summary-sketch", "label": "Weekly summary sketch", "url": "https://example.com/weekly-summary-sketch" },
                { "type": "note", "ref": "weekly-summary-questions", "label": "Open questions for the weekly summary", "url": null }
            ]
        }
    ]
}
```

- `range` is the days the file covers; every card's `date` falls inside it (`YYYY-MM-DD`).
- `lanes` lists every lane the cards use. A lane is created or updated by its `key`: 1 to 40
  lowercase letters, digits or hyphens. `color` is `#rrggbb`; `order` places it on the board.
- One card per lane per day, with a summary of at most 600 characters and at least one source.
  Importing a card replaces the one stored for that day and lane; an import never deletes cards.
- A source's `type` is `commit`, `session`, `artifact` or `note`; `url` is an http(s) address or
  `null`.
- A file with any problem is refused whole, with one message per problem, and nothing is written.

## License

MIT
