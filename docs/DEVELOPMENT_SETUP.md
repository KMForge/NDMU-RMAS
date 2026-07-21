# Development setup

1. Install PHP 8.3–8.5, Composer 2, Node.js/npm, and PostgreSQL client support (`pdo_pgsql`) for Supabase.
2. Copy `.env.example` to `.env`, generate `APP_KEY`, and enter server-side credentials.
3. Run `composer install`, `php artisan migrate --seed`, `npm install`, and `npm run build`.
4. Run `php artisan serve`; use a separate `php artisan queue:work` process for queued work.

The checked-in `.env.example` targets PostgreSQL. Tests override the database to in-memory SQLite. On the current inspected machine, `pdo_pgsql` is missing, so production-like PostgreSQL commands require enabling that PHP extension.

Authentication UI is deliberately not scaffolded in this architecture-only phase. Select a Laravel 13-compatible Blade/Livewire authentication approach before accepting registrations.

Laravel Excel was compatibility-checked but not installed: its current stable dependency constraints do not accept this PHP 8.5/Laravel 13 environment. Do not bypass Composer platform or security checks; reassess an upstream release before implementing spreadsheet exports.
