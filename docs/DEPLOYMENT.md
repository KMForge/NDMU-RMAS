# Deployment

Deploy the built Laravel application with the web root set to `public/`. Configure PHP 8.3+, `pdo_pgsql`, required writable Laravel directories, Nginx/Apache rewrites, a queue worker, and the scheduler (`php artisan schedule:run` every minute).

Install production dependencies with optimized autoloading, build frontend assets in CI or on the host, set secrets outside Git, then run cached configuration/routes/views and `php artisan migrate --force` only after a backup and release review. See `deployment/hostinger`, `deployment/shared/environment-checklist.md`, and `nginx/ndmu-rmas.conf.example`.

The example workflow does not deploy automatically. Configure environment protection, host keys, release paths, and rollback behavior before enabling a deployment job.

## Performance and database placement

Deploy the Laravel application in the same region as the Supabase PostgreSQL project whenever possible. The production web server should connect to PostgreSQL over the provider's private or lowest-latency path; do not run the production application from a developer laptop against a distant database.

For a persistent Laravel server on an IPv4 network, use the Supabase Session Pooler connection shown in the project's **Connect** panel (normally port `5432`) and configure:

```dotenv
DB_CONNECTION=pgsql
DB_SSLMODE=require
DB_PERSISTENT=true
DB_CONNECT_TIMEOUT=10
```

Use the transaction pooler on port `6543` only for serverless or short-lived workers, and review Supabase's prepared-statement restrictions before switching connection modes.

After installing production dependencies and building assets, cache Laravel's bootstrap files:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize
```

Restart PHP workers and queue workers after every deployment. Serve `public/build` and `public/images` with compression and long-lived cache headers for fingerprinted assets.
