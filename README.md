# Harvesta

**Tree & orchard management for small farms.** Record planting blocks, tree types, every individual tree, care activities, harvests, expenses, and sales, then follow productivity and profit on a dashboard and in reports.

**Live demo:** <https://harvesta.wasmer.app>

[![tests](https://github.com/HilmyFausta/harvesta/actions/workflows/tests.yml/badge.svg)](https://github.com/HilmyFausta/harvesta/actions/workflows/tests.yml)

Built for the EF234301 Web Programming midterm. The full product spec is in [`PRD.md`](PRD.md).

---

## Contents

- [Features](#features)
- [Roles](#roles)
- [Tech stack](#tech-stack)
- [Running locally](#running-locally)
- [Demo accounts](#demo-accounts)
- [Quality checks](#quality-checks)
- [Deployment (Wasmer Edge)](#deployment-wasmer-edge)
- [Project structure](#project-structure)
- [Conventions](#conventions)

---

## Features

| # | Feature | What it does |
|---|---------|--------------|
| F-01 | **Dashboard** | Tree, block, and monthly harvest stats; upcoming and overdue care; recent harvests; 12-month harvest chart; sales vs. expenses for admins. Works on phones. |
| F-02 | **Categories** | Master data (for example *Fruit*). Cannot be deleted while tree types use it. |
| F-03 | **Tree Types** | Master data per category (for example *Durian*), with an optional scientific name. |
| F-04 | **Blocks** | Planting areas with size and location; a detail page lists their trees and harvest totals. |
| F-05 | **Trees** | Core record: auto code (`T-0001`), type, block, planting date, status, and one photo. Soft delete. |
| F-06 | **Care Logs** | Watering, fertilizing, pruning, and other activities, with an optional next due date. |
| F-07 | **Harvests** | Quantity in kg or pcs and a grade. Dead trees cannot receive new harvests or care logs. |
| F-08 | **Expenses** | Farm costs by category, per block or general. |
| F-09 | **Sales** | Sales are tied to a harvest. The total is computed on the server, and a sale cannot exceed the unsold quantity. |
| F-10 | **Reports** | Harvest, financial, and care summaries with charts and streamed CSV export. |
| F-11 | **User Management** | Create and edit users and roles. The last admin is protected. |
| — | **Auth & Profile** | Register, login, forgot password, two-factor authentication, and profile, password, and appearance settings. |

Every list has search, filters, sorting, 15-per-page pagination, delete confirmation, flash messages, and an empty state.

Other details:

- **Live password checklist.** Passwords need at least 8 characters, one uppercase letter, and one number. The checklist ticks each rule as you type.
- **Per-page tab icons.** Each page has its own browser tab icon, matching the sidebar.

## Roles

| Role | Access |
|------|--------|
| **Admin** | Everything: all CRUD, master data, finance (expenses, sales), reports, and users. |
| **Staff** | Views all data. Creates and edits trees, care logs, and harvests, and edits only their own care logs and harvests. No deleting, finance, users, or master data changes. People who register themselves become Staff. |
| **Viewer** | Read-only access to trees, care logs, harvests, and master data. |

## Tech stack

- **Backend:** PHP 8.4 (minimum 8.3), Laravel 13, Fortify authentication
- **Frontend:** Livewire 4 single-file components, Flux UI, Tailwind CSS 4, Chart.js, Vite
- **Database:** PostgreSQL 16+ everywhere, including tests
- **Package managers:** Composer and **pnpm** (do not use npm or yarn)
- **Hosting:** Wasmer Edge with Wasmer-managed PostgreSQL. Photos are stored in Supabase Storage over the S3 protocol.

## Running locally

### Requirements

- PHP 8.4 with the `pdo_pgsql`, `pgsql`, `gd`, `intl`, and `zip` extensions
- Composer 2
- Node.js 22+ and pnpm (`npm i -g pnpm`)
- PostgreSQL 16+

### 1. Clone and install

```bash
git clone https://github.com/HilmyFausta/harvesta.git
cd harvesta

composer install
pnpm install
cp .env.example .env
php artisan key:generate
```

### 2. Create the databases

```sql
CREATE DATABASE harvesta;          -- app
CREATE DATABASE harvesta_testing;  -- used by php artisan test
```

Then set your PostgreSQL credentials in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=harvesta
DB_USERNAME=postgres
DB_PASSWORD=your-password
```

### 3. Migrate, seed, and link storage

```bash
php artisan migrate:fresh --seed
php artisan storage:link   # tree photos are served from storage/app/public/photos
```

The seeder creates three accounts, the master data, and 12 months of demo orchard data: 40 trees, 32 photos, care logs, harvests, expenses, and sales. Set `SEED_DEMO_DATA=false` to skip the demo data.

### 4. Start the app

```bash
composer run dev
```

This runs the Laravel server and the Vite dev server together. Open <http://localhost:8000>.

> Alternative: build the assets once with `pnpm run build`, then run `php artisan serve`.
> If the page loads without styles after you stop the dev server, delete `public/hot`.

## Demo accounts

Accounts created by the seeder in local development:

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `password` |
| Staff | `staff@example.com` | `password` |
| Viewer | `viewer@example.com` | `password` |

Change them with the `SEED_*` variables in `.env`. In production, the seeder refuses to run without real `SEED_*_PASSWORD` values.

## Quality checks

CI runs the same three checks on every push to `main` ([`.github/workflows/tests.yml`](.github/workflows/tests.yml)):

```bash
composer test                     # all three checks, exactly like CI

./vendor/bin/pint                 # code style (fix)
./vendor/bin/phpstan analyse      # static analysis, level 7
php artisan test                  # feature tests (needs the harvesta_testing database)
```

`php artisan migrate:fresh --seed` must always succeed.

## Deployment (Wasmer Edge)

Production runs at **<https://harvesta.wasmer.app>**. The full setup, the problems the team hit, and their fixes are in [`docs/DEPLOY_WASMER.md`](docs/DEPLOY_WASMER.md).

### How it works

| Piece | Detail |
|-------|--------|
| Runtime | Wasmer Edge runs the PHP built-in server (`php/php` 8.3.4) with [`php.ini`](php.ini) |
| Package | [`wasmer.toml`](wasmer.toml) mounts the whole project folder at `/app` |
| App config | [`app.yaml`](app.yaml) holds the app name, region (`fr-roub1`), and non-secret env vars |
| Router | [`wasmer-router.php`](wasmer-router.php) serves real files from `public/` and sends everything else, including Livewire and Flux assets, to Laravel |
| Database | PostgreSQL provisioned by Wasmer (`sslmode=require`) |
| Photos | Supabase Storage bucket `harvesta-photos` over S3 (`PHOTO_DISK=s3`) |
| Secrets | `APP_KEY`, `DB_*`, `PHOTO_S3_*`, and `SEED_*_PASSWORD` are set with `wasmer app secrets`, never committed |

**Wasmer does not build anything.** It uploads the local folder as it is, so `vendor/` and `public/build/` must be prepared on your machine first. Files listed in [`.wasmerignore`](.wasmerignore) are not uploaded.

### One-time setup

```bash
curl https://get.wasmer.io -sSfL | sh
wasmer login
```

### Redeploy after changes

```bash
git pull
composer install
pnpm install
pnpm run build

# Stop the dev server first and make sure public/hot does not exist,
# otherwise the deployed site will look for CSS/JS on localhost.
rm -f public/hot

wasmer deploy        # answer "y" if it offers a version bump
```

Then open <https://harvesta.wasmer.app> with a hard refresh (Ctrl+Shift+R).

### Database migrations

The WASI sandbox cannot spawn subprocesses, so migrations are **not** run on boot. When a release adds a migration, run it from your machine against the Wasmer database:

1. Back up your `.env` and temporarily point its `DB_*` values to the Wasmer database (credentials are in the Wasmer dashboard, under **Databases**).
2. Run `php artisan migrate --force` (and `php artisan db:seed --force` on a fresh database).
3. Restore your local `.env`.

### Managing secrets

```bash
wasmer app secrets list
wasmer app secrets create NAME "value"     # new secret
wasmer app secrets update NAME "value"     # existing secret
wasmer deploy                              # redeploy so changes take effect
```

## Project structure

```
app/
├── Enums/                 TreeStatus, UserRole, HarvestUnit, ... (labels + select options)
├── Models/                Eloquent models; Concerns/RecordsAuthor fills user_id automatically
├── Policies/              Per-record authorization (auto-discovered)
├── Support/               Format (dates, numbers, money, time until), Favicon
└── Http/Controllers/      ReportExportController (streamed CSV)
database/
├── migrations/            PostgreSQL schema (see PRD section 6)
└── seeders/               Users, master data, demo data + demo photos
resources/views/
├── pages/<module>/        Livewire single-file pages (⚡index, ⚡create, ⚡edit, ⚡show)
├── components/            page-header, empty-state, tree-status-badge, password-requirements, ...
└── layouts/               App (sidebar) and auth layouts
routes/web.php             All app routes; admin-only routes sit in the role:admin group
public/favicons/           Per-page tab icons
docs/DEPLOY_WASMER.md      Deployment guide and troubleshooting
```

## Conventions

The short version is below. [`AGENTS.md`](AGENTS.md) has the full list, and [`PRD.md`](PRD.md) is the source of truth.

- UI text is in English. Commits follow [Conventional Commits](https://www.conventionalcommits.org/).
- Schema changes always go in a **new** migration.
- Lists paginate by 15 and eager-load relations (no N+1).
- Validate on the server. `sales.total_amount` and author columns are never taken from input.
- Photos only go through `Storage::disk('photos')` and are read with `$tree->photo_url`.
- Runtime code must work on Wasmer: no `exec()`, no reliance on local disk, and no PHP 8.4-only syntax in executed code paths.
