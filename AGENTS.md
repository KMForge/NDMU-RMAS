# NDMU-RMAS contributor instructions

## Project

NDMU-RMAS is a Laravel 13 modular monolith for university research registration, review, progress, consultation, revision, defense, evaluation, reporting, notification, auditing, and archiving.

## Stack and boundaries

- PHP 8.3+, Laravel 13, Eloquent, Livewire 4, Blade, Alpine (bundled by Livewire), Tailwind CSS 4, Vite.
- PostgreSQL hosted by Supabase; query it directly through Laravel's `pgsql` driver.
- Use Supabase HTTP APIs only for Storage, signed URLs, Realtime, and capabilities unavailable through PostgreSQL.
- Roles and permissions use Spatie Laravel Permission. A user may have multiple roles; never add a single `role` column.

## Structure and naming

- Put shared models in `app/Models` and domain logic in PascalCase `app/Modules/<Domain>` folders.
- HTTP controllers stay in `app/Http/Controllers`; do not duplicate them inside modules.
- Use PascalCase PHP classes, singular model names, plural snake_case tables, and kebab-case object paths/assets.
- Flow: controller/Livewire → request/component validation → action/service → optional repository → model → policy.
- Add module subfolders only when they contain real code. Avoid placeholder classes and empty directories.

## Commands

```bash
composer install
php artisan migrate --seed
php artisan test
vendor/bin/pint --test
npm install
npm run build
```

Use `scripts/setup.ps1` on Windows or `scripts/setup.sh` on Unix-like systems.

## Rules

- Never edit an applied migration. Create a new, reversible migration with foreign keys, indexes, and constraints. Use integer IDs until an architecture decision explicitly changes the convention.
- Enforce authentication, verified email, active/approved account, role/permission middleware, and record-scoped policies server-side.
- Policies must consider ownership, membership, adviser/panel assignment, college scope, and explicit administrative permission as appropriate.
- Validate every form and file. Restrict MIME type/size, generate random object names, retain original names only as metadata, and reject traversal paths.
- Confidential files belong in private Supabase buckets (or `storage/app/private` locally), accessed by short-lived signed URLs or authorized download endpoints.
- Never hard-code or commit secrets. The Supabase service-role key is server-only and must never use a `VITE_` prefix.
- Use transactions for multi-step approvals and audit important administrative actions without sensitive values or signed URLs.
- Run formatting, backend tests, and the frontend build before declaring work complete.
