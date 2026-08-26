# Phase 25 — Reports and Analytics

## Objective and boundary

Phase 25 adds read-only operational reporting for the College of Engineering, Architecture, and Computing (CEAC). It does not mutate research records, introduce a second research-project model, expose private evaluation scores, create public export files, or implement Phase 26/27 work.

The authoritative aggregate root is `research_class_groups`. Only groups linked to the canonical `research_groups` record are reportable because that relationship supplies the verified `program_id` and `academic_term_id`. Program scope is verified through `programs -> departments -> colleges`, where `colleges.code = CEAC`.

## Supported actors and scope

- Administrator: CEAC-wide reports only when `reports.view` is granted; export additionally requires `reports.export`.
- Dean: the same CEAC-wide read-only scope and explicit permissions.
- Research Facilitator: only groups belonging to `research_classes.facilitator_id = authenticated user ID`. Permission alone never widens ownership.

All routes retain the workspace middleware (`auth`, `verified`, `active`, dashboard permission, and `workspace.context`) and add report permission and named throttles. `ReportPolicy` is evaluated again by the controller. Filter authorization is resolved before report data is queried.

## Report catalog

`App\Modules\ReportsAnalytics\ReportCatalog` is the versioned registry for stable identifiers, labels, descriptions, applicable filters, and ordered output columns.

1. `research-stage-status` — groups by current incomplete milestone and lifecycle status.
2. `research-summary` — active, completed, delayed, and overdue group totals.
3. `milestone-completion` — weighted completion per group.
4. `research-output-program` — group output by CEAC program.
5. `research-throughput` — verified milestone transitions to `completed` grouped by month.
6. `defense-types` — status totals for the four verified defense types.
7. `defense-status` — draft/pending, scheduled, completed, and cancelled occurrences.
8. `adviser-workload` — current adviser assignments for groups not completed.
9. `document-review-status` — current document versions and non-superseded review presence.
10. `revision-summary` — revision status and overdue totals.
11. `evaluation-release-status` — released versus not released using `released_at`.

## Metric definitions

- A group is completed only when it has milestones and every milestone is `completed` or `not_applicable`.
- A group is overdue when at least one pending/in-progress milestone has a past `due_at`, or at least one unresolved/non-cancelled revision has a past `due_at`. A group is counted once in group summaries.
- The schema has no distinct delayed lifecycle state. The report exposes a zero/unavailable delayed category instead of inventing a time rule.
- Weighted completion is `completed applicable weight / total applicable weight * 100`, rounded to two decimals. Not-applicable milestones are excluded from numerator and denominator. A zero denominator returns 0 safely.
- Defense occurrences use `defenses.status`; rescheduled rows in `defense_schedules` are not counted as new defenses.
- Evaluation release uses `defense_evaluation_rounds.released_at`, never score presence.
- Document reporting scopes through `documents.research_class_group_id`, uses only `is_current = true`, and ignores superseded reviews.
- Throughput counts persisted `research_group_milestone_events` whose `to_status` is `completed`; it is not a group-creation chart.

## Filters

`ReportFilterRequest` validates academic year, academic term, program, research class, adviser, milestone stage, status, date range, and pagination. Each report declares only meaningful filters. The immutable `ReportFilters` DTO is shared by HTML, CSV, and PDF.

Additional fail-closed checks ensure:

- a selected term belongs to the selected year;
- a selected program belongs to CEAC;
- a facilitator-selected class is owned by that facilitator;
- a selected adviser occurs inside the actor's reportable group scope.

Dates are normalized as `Y-m-d`. Operational reports apply dates to their authoritative event/record timestamp; group reports apply dates to group creation.

## Routes

Each prefix (`admin`, `dean`, and `facilitator`) provides:

- `GET /{workspace}/reports` — report catalog;
- `GET /{workspace}/reports/{report}` — filtered, paginated HTML result;
- `GET /{workspace}/reports/{report}/csv` — CSV download;
- `GET /{workspace}/reports/{report}/pdf` — PDF download.

Route names follow `{workspace}.reports.index|show|csv|pdf`. Existing workspace navigation links to the dedicated catalog without replacing unrelated dashboards.

## Exports and privacy

CSV uses PHP's `fputcsv`, a stable catalog column order, UTF-8 BOM, `text/csv`, server-generated filenames, and streamed responses. Cells whose first meaningful character is `=`, `+`, `-`, `@`, tab, CR, or LF are prefixed with an apostrophe to prevent spreadsheet formula execution. Commas, quotes, and newlines are escaped by the CSV writer.

PDF uses the already-installed `barryvdh/laravel-dompdf` package and a server-controlled Blade template. It contains the report name, generation time, scope, filters, headings, and authorized rows. Blade escapes database text.

Both formats enforce `analytics.max_export_rows` (default 10,000) and return HTTP 422 rather than silently truncating. Exports are immediate downloads. No report file, private path, signed URL, or permanent public URL is created.

## Cache and performance

Report cache keys include catalog version, report identifier, actor/workspace scope fingerprint, facilitator-owned class identifiers, and normalized filters. The TTL comes from `analytics.cache_ttl` (default 900 seconds). Cached values are arrays rather than authorization objects or Eloquent models.

Queries select required fields and bulk-load milestone and revision state in a bounded number of queries. Results are capped at the configured export limit plus one, and HTML rows are paginated at 25 records. No speculative migration or database index was added; existing CEAC, group ownership, status/due-date, defense, document, review, revision, and evaluation indexes support the access patterns.

## Audit integration

The central Phase 24 `AuditLogWriter` records:

- `report.viewed` after a report result is produced;
- `report.exported` after an authorized CSV/PDF payload is produced.

Bounded, sanitized metadata contains report identifier, format, scope label, row count, and normalized filters. It never contains export bytes, paths, signed URLs, cookies, tokens, or evaluation details.

## Files and database changes

The implementation adds a report catalog, immutable scope/filter value objects, scope resolver, shared query/aggregation service, secure exporters, validated request, thin controller, dedicated Blade templates, workspace routes, rate limiters, audit event identifiers, tests, and this documentation.

No migration or new index was required.

## Verification

Focused Phase 25 coverage currently verifies guest denial, permission-driven Administrator access, separate export permission, CSV headers and centralized audit recording, facilitator cross-class IDOR rejection, CSV formula-prefix mitigation, and matching empty HTML/PDF scope. The focused suite passes with **7 tests and 29 assertions**.

The following gates pass:

- `vendor/bin/pint --test`
- report route inspection: 12 named Admin, Dean, and Facilitator routes
- `php artisan migrate:status`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `npm run build`
- `git diff --check`

The repository-wide suite is not green: **447 tests were executed; 421 passed, 24 were skipped, and 2 failed, with 1,833 assertions**. Both failures are existing welcome-page contract mismatches: `WelcomePageTest` expects copy from the previous landing page, while `DashboardRedirectTest` expects Login/Register navigation that the current landing-page redesign does not render. Phase 25 does not modify the welcome page or those tests. A focused rerun of the two affected files confirmed 5 passing and 2 failing tests.

`composer validate` was not run because Composer is not available on this shell's `PATH` and no project-local `composer.phar` is present. For these reasons Phase 25 is recorded as implemented but not yet complete under the prompt's strict repository-wide verification rule.

## Known limitations and non-goals

- Class groups without `research_group_id` are excluded because program/term/CEAC scope cannot be proven safely.
- “Delayed” is not inferred; the current schema only supports the defensible overdue definition above.
- Reports do not expose individual evaluation scores.
- No XLSX, external BI, scheduled email, real-time analytics, queue-backed export lifecycle, or permanent export storage is included.
