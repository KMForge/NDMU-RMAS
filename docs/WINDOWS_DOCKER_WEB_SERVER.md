# Concurrent web hosting on Windows

The PHP built-in server is single-process on Windows. `PHP_CLI_SERVER_WORKERS`
does not enable concurrent workers there. Use the separate web stack instead:

```powershell
$env:NDMU_WEB_PORT = '8001'
docker compose -f docker-compose.web.yml up -d --build php web
docker cp .\vendor ndmu-rmas-web-php-1:/var/www/html/
docker compose -f docker-compose.web.yml exec php php artisan config:cache
docker compose -f docker-compose.web.yml up -d queue
```

Run `composer install` on the host first if `vendor/` is absent. Dependencies
are copied to the `php-vendor` Docker volume to avoid slow Windows bind-mount
reads. Refresh this copy after changing Composer dependencies, with PHP and
queue processes stopped. Source code, private storage, and assets remain shared.

The default test port is `http://127.0.0.1:8001`. It does not modify or replace
the PostgreSQL container or its volume. The PHP services connect to the existing
host database using `host.docker.internal`; credentials remain in `.env`.
Nginx serves only `public/`, and PHP-FPM supports up to eight concurrent requests.
The host port is loopback-only, intended for Cloudflare Tunnel ingress.

After verifying the test port, stop the old Laravel server and set the web port:

```powershell
$env:NDMU_WEB_PORT = '8000'
docker compose -f docker-compose.web.yml up -d
```

The existing Cloudflare application origin `http://127.0.0.1:8000` then needs no
change. Set `NDMU_WEB_PORT=8000` in `.env` to persist the selected port across
PowerShell sessions. Keep Reverb and the scheduler running separately on this device.
Reverb must be reachable from Docker at `host.docker.internal:8085`.
Do not run the legacy `serve-production.ps1` web supervisor on port 8000 at the
same time as this stack.

Use `QUEUE_CONNECTION=database` and `LOG_LEVEL=warning` in `.env`. Uploads save
the private file and version first, then enqueue metadata extraction after the
transaction commits. The queue service consumes both `default` and `documents`.
Do not use the `sync` queue connection for public uploads; it runs jobs inline.
PostgreSQL 17 client tools are installed for backup and restore compatibility.
Admin upload settings remain enforced below the 100 MB PHP infrastructure ceiling.

Docker services bypass the host's cached Windows configuration. Cache configuration
inside each PHP container after changing `.env`, then restart its processes:

```powershell
docker compose -f docker-compose.web.yml exec php php artisan config:cache
docker compose -f docker-compose.web.yml exec queue php artisan config:cache
docker compose -f docker-compose.web.yml restart php queue
docker compose -f docker-compose.web.yml logs --tail=30 php queue
```

Container caches live in `/tmp` and are rebuilt after recreation. Private files,
signature keys, uploaded papers, and backup archives remain in the shared project
storage. Do not delete database volumes to restart the web stack.
