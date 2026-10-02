# NDMU-RMAS performance audit — 2026-10-02

## Scope and measurement method

This audit covered the login path and the primary dashboard request for student, adviser, panelist, facilitator, dean, and administrator roles. It also inspected Blade rendering, Livewire state, Eloquent access patterns, assets, middleware, session/cache/queue configuration, PHP OPcache, Docker, and the Cloudflare origin.

`scripts/profile-dashboard-performance.php` boots the real application, resolves a real authorized user per role, dispatches the real dashboard route, and records elapsed time, SQL count/duration, response size, memory, repeated SQL, and slow SQL. It intentionally bypasses browser middleware and therefore measures server-side route/action/view work, not browser paint or Cloudflare latency.

`scripts/profile-login-performance.php` measures credential verification, verified-student activation, role route resolution, and audit recording. The audit insert is rolled back.

The database is hosted remotely on Supabase, so elapsed and SQL duration vary with network latency. Query counts and generated response sizes were repeatable and are the stronger comparison. The table uses the same profiler and data before and after the changes.

## Before and after

| Workspace | Warm total before | Warm total after | Queries before → after | HTML before → after |
| --- | ---: | ---: | ---: | ---: |
| Student | 279.66 ms | 173.62 ms | 49 → 34 | 262.75 → 73.78 KB |
| Adviser | 475.33 ms | 215.97 ms | 74 → 45 | 305.23 → 80.17 KB |
| Panelist | 279.10 ms | 138.77 ms | 49 → 24 | 339.39 → 94.40 KB |
| Facilitator | 399.46 ms | 156.82 ms | 66 → 27 | 584.80 → 214.49 KB |
| Dean | 373.27 ms | 207.85 ms | 111 → 43 | 25.88 → 25.88 KB |
| Administrator | 262.12 ms | 28.60 ms | 70 → 2 | 673.24 → 98.33 KB |

All post-change warm samples reported zero duplicate SQL groups. Repeated after-change samples still showed network-related timing variation, but retained the same query counts and response sizes.

The measured post-change administrator login server flow was 287.08 ms warm: password authentication 275.07 ms, activation 0.09 ms, route selection 8.72 ms, and audit recording 3.20 ms. Password hashing is intentionally expensive and remains the dominant login operation.

## Confirmed root causes and fixes

### Hidden dashboards performed visible work

The role dashboards rendered every tab into hidden HTML and their controllers loaded data for every tab. This inflated queries and responses on first login.

- `resources/views/pages/*-dashboard.blade.php` now renders only the active tab.
- Student, adviser, panelist, and facilitator controllers now load tab-specific data only.
- `app/Livewire/AdminDashboard.php` and `resources/views/livewire/admin-dashboard-content.blade.php` now lazily load and render the selected administrator panel.
- Livewire tab state remains server-authoritative and navigation uses stable anchors with `wire:navigate`.

### Repeated and N+1 database access

- `GetAdviserDashboardOverview` batches projects and milestones instead of querying per advisee/group.
- `GetDefenseScheduleCalendar` batches RES-037 instances instead of querying inside the schedule loop.
- Official-form pending actions eager-load the policy, actor, group, program, signature, and panel relations used during authorization.
- `InstitutionalActorResolver` and `ResearchJourneyService` memoize request-local resolution work.
- `OfficialFormAuthorization` and `OfficialFormInstancePolicy` use loaded relationships instead of repeatedly reloading them.
- `GetDeanDashboardData` loads groups, documents, or schedules only for the requested tab and aggregates counts in SQL.
- `GetFacilitatorClassData` avoids impossible enrollment queries when the facilitator owns no classes.
- `UnreadNotificationCount` shares one scoped count per user/request.
- `ResearchClassGroup::title` uses the eager-loaded current project before falling back to SQL.

### Login performed an unnecessary locked transaction

`ActivateVerifiedStudent` previously entered a transaction and locked the user row for every successful login. Active users, non-students, and unverified students now return before the transaction; pending verified students are still rechecked under the lock, preserving correctness.

### Synchronous health checks and repeated refresh behavior

- The administrator dashboard no longer starts `pg_dump --version` or `docker exec` subprocesses during page rendering.
- Facilitator monitoring refreshes at 60 seconds and only while visible/focused; teardown removes the timer.
- No `wire:poll`, recursive request cycle, redirect loop, or unbounded application loop was found.
- Research-class join code generation is bounded to ten attempts.

### Frontend blocking and duplication

- The global blocking Google Playfair stylesheet import was removed; the display stack uses a local/system fallback.
- A duplicate landing-page scroll listener was removed.
- PDF and DOCX viewers remain dynamic imports, so the 1.2 MB PDF worker and document libraries are not part of the initial 67.11 KB JavaScript entry.

## Remaining bottlenecks and evidence limits

1. **Production is exposed through `php artisan serve`.** The Cloudflare tunnel targets `http://127.0.0.1:8000`, backed by Laravel's single-worker development server. Concurrent defense requests are serialized and can queue for a long time. Use IIS/FastCGI, Apache, or Nginx with PHP-FPM and multiple workers; then point the tunnel at that origin.
2. **Remote database latency remains material.** No individual warm business query was consistently slow enough to justify an index from this sample. Indexes were not added without `EXPLAIN (ANALYZE, BUFFERS)` evidence.
3. **Runtime schema introspection remains.** Hot paths still contain `Schema::hasTable`/`hasColumn`; PostgreSQL catalog checks were visible in profiles. Removing these needs a separate compatibility cleanup after confirming every deployment is fully migrated.
4. **Browser paint and Livewire network timing were not measured.** Local authenticated automation is blocked by the production secure-cookie/Turnstile setup, and the environment has no authenticated browser trace. Capture Chrome DevTools/Lighthouse data in a staging domain to establish LCP, INP, transferred bytes, and Livewire request waterfalls.
5. **Turnstile can wait up to its outbound timeout.** This is a suspected external dependency, not a confirmed current bottleneck; server-side timing requires a controlled login trace with Turnstile enabled.
6. **File cache/session drivers are local-disk based.** They are acceptable for one host but should be moved to Redis (or a shared supported store) before horizontal scaling. This recommendation was not benchmarked in this audit.

## Validation

- Full regression suite: **562 tests; 539 passed, 23 intentionally skipped; 2,808 assertions**.
- Final focused research-progress and official-form suite: **84 passed; 378 assertions**.
- Final focused class workflow suite: **20 passed, 21 intentionally skipped; 107 assertions**.
- `vendor/bin/pint --test`: passed.
- `npm run build`: passed; Vite 8.1.5 built 234 modules.
- Initial production assets: CSS 337.44 KB (51.81 KB gzip), JavaScript 67.11 KB (23.77 KB gzip).
- `git diff --check`: passed.

## Production deployment

Run after deploying the code and setting production environment values:

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
```

Use `APP_ENV=production`, `APP_DEBUG=false`, and enable PHP OPcache in the web SAPI. When `opcache.validate_timestamps=0`, restart the PHP worker after every deployment. Do not use `php artisan serve` as the public production origin.

To repeat the measurements:

```powershell
php scripts/profile-dashboard-performance.php
php scripts/profile-login-performance.php admin@ndmu.edu.ph "<password>"
```

Do not put the password in shell history on a shared computer; use a temporary evaluation account when possible.
