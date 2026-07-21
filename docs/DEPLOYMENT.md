# Deployment

Deploy the built Laravel application with the web root set to `public/`. Configure PHP 8.3+, `pdo_pgsql`, required writable Laravel directories, Nginx/Apache rewrites, a queue worker, and the scheduler (`php artisan schedule:run` every minute).

Install production dependencies with optimized autoloading, build frontend assets in CI or on the host, set secrets outside Git, then run cached configuration/routes/views and `php artisan migrate --force` only after a backup and release review. See `deployment/hostinger`, `deployment/shared/environment-checklist.md`, and `nginx/ndmu-rmas.conf.example`.

The example workflow does not deploy automatically. Configure environment protection, host keys, release paths, and rollback behavior before enabling a deployment job.
