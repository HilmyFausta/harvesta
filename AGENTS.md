# AGENTS.md

Read `PRD.md` first. It is the single source of truth (scope, roles, schema, ownership, Git flow). This file only lists the technical conventions that already exist in the codebase so every agent builds on them instead of reinventing them.

## Stack

- PHP 8.4 (min 8.3), Laravel 13, Livewire 4 + Flux UI (free), Tailwind CSS 4, Fortify auth.
- PostgreSQL 16+ everywhere, including tests (`harvesta_testing` database, see `phpunit.xml`). Never SQLite/MySQL.
- pnpm only (`pnpm-lock.yaml`). Never commit `package-lock.json`.
- Livewire pages are single-file components (`resources/views/pages/**/⚡name.blade.php`) registered with `Route::livewire('url', 'pages::module.name')`. Follow `resources/views/pages/settings/` as the reference.

## Commands

```bash
composer run dev                    # app + vite
php artisan migrate:fresh --seed    # must always succeed
./vendor/bin/pint                   # format (CI fails otherwise)
./vendor/bin/phpstan analyse        # level 7 (CI fails otherwise)
php artisan test                    # needs the harvesta_testing database
```

`composer test` runs all three checks exactly like CI.

## Conventions you must reuse

| Need | Use |
|------|-----|
| Enum values / select options | `App\Enums\*` → `TreeStatus::options()`, `Rule::in(TreeStatus::values())` / `Rule::enum(TreeStatus::class)`, `$status->label()` |
| Tree status badge | `<x-tree-status-badge :status="$tree->status" />` |
| Page title + action buttons | `<x-page-header :title="__('Trees')">…buttons…</x-page-header>` |
| Empty state | `<x-empty-state icon="…" :title="__('No trees yet.')">…action…</x-empty-state>` |
| Flash / confirm / rule messages | `__('messages.created', ['item' => __('tree')])`, `__('messages.in_use.category')`, etc. in `lang/en/messages.php` |
| Dates / numbers / money | `App\Support\Format::date()` → `21 Oct 2026`, `::number()` → `1,250,000.50`, `::money()` → `IDR 1,250,000` |
| Admin-only routes | inside the `role:admin` group in `routes/web.php` |
| Per-record access | Policies in `app/Policies` (auto-discovered): `$this->authorize('update', $careLog)`, `@can('delete', $tree)` |
| "Is admin?" in Blade | `@can('admin')` (gate), never `@if ($user->role === …)` |
| Author columns (`user_id`, `created_by`) | filled automatically on create by `App\Models\Concerns\RecordsAuthor` — do not set them from user input |
| `sales.total_amount` | computed in `Sale::saving()` — never accept it from input |
| Remaining harvest quantity | `$harvest->remainingQuantity()` (eager-load with `withSum('sales', 'quantity')` on lists) |
| Dead trees | `$tree->acceptsRecords()` before creating care logs/harvests |
| Tree code | leave `code` blank to auto-generate `T-0001`, `T-0002`, … |
| Photos | only `Storage::disk('photos')`; read with `$tree->photo_url`. Old files are deleted automatically on replace/force delete |
| App name | `config('app.name')`, never a literal |

## Rules

- All UI text is correct English (Title Case headings, sentence case buttons, full sentences with a period for messages). Code, commits, and branch names are English.
- Lists: paginate 15, eager-load relations (`with()`), no N+1.
- Validate on the server; protect mass assignment with `#[Fillable]`.
- Schema changes go in a **new** migration, plus PRD section 6 update, announced to the team.
- Only edit your own area (PRD section 12). Shared files (layout, `routes/web.php`, providers, config) change in small, separate commits. To ship a module, replace its placeholder `Route::view(... 'coming-soon' ...)` line and keep the `<module>.index` route name.
- The team works directly on `main` (no feature branches, no PRs). Use Conventional Commits and keep `main` runnable. Never push yourself; the human pushes.
- Do not report a task as done while Pint, PHPStan, or tests fail.
