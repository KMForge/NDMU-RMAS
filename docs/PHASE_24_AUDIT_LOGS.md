# Phase 24 — Audit Logs

## Purpose and phase boundary

Phase 24 provides NDMU-RMAS with one central, append-only accountability trail for important security, administrative, and cross-module workflow mutations. It answers who acted, the verified function or workspace used, what record was affected, what safe state changed, the outcome, time, IP address, and bounded client context.

The trail observes authoritative actions. It does not authorize users, execute workflow transitions, send notifications, replace academic records, export reports, purge records, or automatically progress research work. Exports belong to Phase 25.

## Actors and context

Permanent account types remain Student, Faculty, and Admin. Faculty function is derived server-side from the authoritative action and its assignments:

- `research-facilitator` for owned classes, join decisions, group management, and adviser assignments;
- `thesis-adviser` for assigned consultations, revisions, and progress work;
- `student-researcher` for group-leader document submissions and student revision work;
- `administrator` for user, RBAC, and system-setting administration;
- a validated active workspace for `workspace.switched`.

Existing form, defense, and evaluation actions derive context from exact form actor assignments, owning facilitator relationships, defense panel assignments, and frozen evaluation rosters. The browser cannot supply actor ID, actor snapshots, actor context, or outcome.

Unauthenticated events require the writer's explicit `allowSystemActor` flag. Failed-login identifiers are stored as a keyed SHA-256 HMAC instead of raw email addresses.

## Existing foundations reused

Phase 24 extends rather than replaces:

- `App\Models\AuditLog` and the `audit_logs` table;
- nullable actor relation and durable actor/subject snapshots;
- the Spatie `audit-logs.view` permission;
- the existing Audit Logs tab in `AdminDashboard`;
- existing workspace, account, role, settings, form, signature, title-presentation, defense, and evaluation events;
- every specialized feature-history table.

## Central versus specialized evidence

`audit_logs` contains concise cross-system security and accountability events. It is not the academic source of truth.

Detailed authoritative evidence remains separate:

- documents: `document_upload_audits`, `document_access_audits`, `document_reviews`, `document_review_comments`, and `document_review_audits`;
- consultations: `consultation_requests`, `consultation_schedule_proposals`, `consultation_records`, `consultation_attendances`, and `consultation_audits`;
- revisions: `revision_requests` and their event records;
- progress: `research_group_milestones`, `research_group_milestone_events`, and `milestone_evidences`;
- forms/signatures: `official_form_instances`, `official_form_versions`, `official_form_actor_assignments`, `official_form_signatures`, and `official_form_verifications`;
- defenses: `defenses`, `defense_schedules`, and `defense_panel_assignments`;
- evaluations: frozen evaluation rounds, panel/student rosters, evaluations, scores, and summaries;
- `notifications`, which are delivery/read-state records rather than audit evidence.

The central event references the authoritative record without copying complete domain payloads.

## Route, method, middleware, and permission

The existing viewer is used; there is no duplicate audit dashboard and no mutation endpoint.

| Method | URI | Route name | Purpose |
| --- | --- | --- | --- |
| `GET` | `/admin/dashboard?tab=audit` | `admin.dashboard` | Open the existing Audit Logs tab. |

The route retains `web`, `auth`, verified-email, active/approved-account, `dashboards.admin.view`, and workspace-context protection. Audit retrieval separately requires `audit-logs.view`:

- `AdminDashboard::mount()` rejects a direct audit-tab URL without it;
- `AdminDashboard::updatedTab()` rejects Livewire property tampering;
- `GetAuditLogsForAdmin` rechecks it before querying;
- sidebar and content use Blade `@can` guards;
- overview cache keys separate viewers with and without audit permission.

There is no POST, PUT, PATCH, or DELETE route for audit logs.

## Validation and safe query behavior

`AuditLogWriter` accepts only keys in `AuditEvent` and outcomes `succeeded`, `denied`, or `failed`. It bounds actor context to 64 characters, descriptions and scalar payload strings to 500, snapshots to 255, IP addresses to 45, user agents to 500, JSON depth to 6, each collection to 100 entries, and serialized state to 16 KiB. Oversized state is replaced with deterministic truncation metadata and a SHA-256 digest.

Viewer search is HTML-stripped and limited to 100 characters. Event/context filters must exactly match stored values. Outcome is allowlisted. Dates must be exact `Y-m-d` values and become inclusive day boundaries. Eloquent supplies parameter binding. Ordering is `created_at DESC, id DESC`. Only the actor relation is eager loaded; arbitrary polymorphic subjects are not loaded, preventing unsafe morph loading and N+1 behavior.

## Components, services, and actions

### Audit module

- `AuditLogWriter`: validates and inserts a central record.
- `AuditPayloadSanitizer`: recursive redaction, safe normalization, and bounds.
- `AuditRequestContext`: immutable IP, user agent, and workspace input assembled at the request boundary.
- `AuditEvent`: stable event allowlist.
- `GetAuditLogsForAdmin`: permission-protected viewer query and statistics.

### HTTP and Livewire

- `AuthenticatedSessionController`: login success/failure/blocked and logout.
- `NewPasswordController`: completed password reset.
- `VerifyStudentEmailController`: first successful verification.
- `RegisteredStudentController`: supplies request context to registration.
- `AdminDashboard`: authorization, filters, query integration, and safe cache boundary.

### Domain actions integrated

- registration, user status, RBAC, system settings, and workspace switching;
- research class creation and join-request decisions;
- research group creation, leader assignment, and adviser assignment;
- secure document and revision submission;
- completed consultation recording;
- revision status transitions;
- research milestone transitions and corrections.

Mature form, signature, title-presentation, defense, and evaluation action classes retain their already verified central events. Replacing every direct writer at once was intentionally avoided to protect academic workflows; migration is incremental.

## Audit writer process and transactions

1. Request/component validation runs.
2. Permission, policy, ownership, membership, and contextual-assignment checks run.
3. The action acquires its existing cache/database locks.
4. The domain mutation and specialized history are written.
5. The critical central event is written in the same transaction.
6. Actor/event/outcome are validated and safe snapshots are normalized.
7. The transaction commits both domain state and audit evidence, or rolls both back.

The writer never changes academic state and never silently swallows a critical persistence failure. Denied authentication events are written outside a mutation transaction because no successful domain mutation exists. Existing uniqueness constraints, lock retries, double-click protection, and notification after-commit behavior remain unchanged.

## Model, database, and relationships

`AuditLog` has `actor()` (`belongsTo User`), `auditable()` (polymorphic), array casts for old/new values, immutable date casting, and no `updated_at`. `user_id` remains nullable with `nullOnDelete`, so snapshots survive actor deletion. Polymorphic subject snapshots survive source deletion.

Migration `2026_08_25_000001_harden_audit_logs_for_phase24.php` adds:

- nullable `actor_context varchar(64)`;
- `outcome varchar(16)` default `succeeded`;
- `(actor_context, created_at)` and `(outcome, created_at)` indexes, matching viewer filters.

Historical context stays null because it cannot be inferred safely. Historical outcomes receive the deterministic `succeeded` default.

On PostgreSQL, `prevent_audit_log_mutation()` and `audit_logs_append_only` reject UPDATE and DELETE. The only permitted update is a foreign-key-driven change from non-null `user_id` to null with every other value unchanged. The migration and trigger are reversible and do not assume Supabase roles.

## Event naming catalog

Compatibility keys remain unchanged: `workspace.switched`, `user.registered`, `user.created`, `user.approved`, `user.rejected`, `user.activated`, `user.suspended`, `user.access-updated`, `role.created`, `role.updated`, `role.deleted`, and `system-settings.updated`.

New catalog domains are `auth.*`, `class.*`, `research-group.*`, `adviser.*`, `document.*`, `consultation.*`, `revision.*`, `research.milestone.*`, `official-form.*`, `signature.*`, `defense.*`, and `evaluation.*`. Keys describe actions and do not rename academic statuses.

## Sensitive-data redaction

The sanitizer recursively redacts keys associated with passwords, tokens, credentials, authorization/cookies/sessions, CSRF, API/access/refresh keys, private/stored paths, signed/temporary URLs, file content/binary values, signature images/paths, HMAC, and secrets.

The writer is never given `$request->all()`, raw file data, complete models, unfiltered form payloads, private object paths, or signed URLs. Unsupported objects/resources become inert placeholder strings. Descriptions are server-generated and HTML-stripped. The viewer uses escaped Blade output and does not show raw user agents, raw JSON, or arbitrary HTML.

## Business rules, scope, and contextual RBAC

Existing workflow rules remain authoritative. Examples:

- a join decision is audited only after pending state, class ownership/activity, capacity, and single-enrollment checks pass under row locks;
- document submission is audited only after leadership, validation, duplicate prevention, private storage, and record creation succeed;
- consultation completion requires the currently assigned adviser and an approved request;
- milestone corrections are distinguished from ordinary transitions and retain detailed milestone events;
- form, panel, defense, and evaluation actions continue to use their exact contextual assignments.

A Spatie role grants capability but does not by itself establish the academic context. The authoritative assignment establishes the context recorded.

## Append-only, IDOR, and failure behavior

- model update/delete throws `LogicException`;
- PostgreSQL direct update/delete is blocked;
- no edit/delete/clear UI or route exists;
- viewer permission is independent of Admin account type;
- snapshots are displayed rather than using source-record access as authorization;
- an auditable ID never grants access to the source record.

Failure responses preserve existing user-facing behavior. Audit-specific failures are HTTP 403 for unauthorized viewer access/tampering, `InvalidArgumentException` for an unknown event/outcome or unapproved null actor, `LogicException` for Eloquent mutation, and a PostgreSQL exception for direct mutation.

## Files added

- `app/Modules/AuditLogs/Services/AuditLogWriter.php`
- `app/Modules/AuditLogs/Support/AuditEvent.php`
- `app/Modules/AuditLogs/Support/AuditPayloadSanitizer.php`
- `app/Modules/AuditLogs/ValueObjects/AuditRequestContext.php`
- `app/Modules/AuditLogs/Queries/GetAuditLogsForAdmin.php`
- `database/migrations/2026_08_25_000001_harden_audit_logs_for_phase24.php`
- `tests/Feature/AuditLogs/AuditLogWriterTest.php`
- `tests/Feature/AuditLogs/AuditLogViewerAuthorizationTest.php`

## Files modified and why

- `AuditLog.php`: structured fields and model immutability.
- authentication and registration: security/account lifecycle events.
- administration, authorization, and user management: reusable writer replaces duplicate helpers.
- selected Classes, Documents, Consultations, Revisions, and ResearchProgress actions: transactionally consistent central evidence.
- `AdminDashboard.php` and its Blade views: strict authorization, query extraction, safe filters/rendering, and permission-safe caching.
- Audit Logs README and workspace documentation: current architecture and structured actor context.
- `PROJECT_PROGRESS.md`: verified state after all gates.

## Focused tests and executed evidence

Tests cover actor/subject snapshots, context/outcome, redaction, bounds, explicit system actors, model immutability, transaction rollback, snapshot survival, viewer authorization, Livewire tampering, exact filters, inactive/unverified users, and absence of mutation routes.

Executed during implementation:

- Phase 24 audit suite: **11 passed, 30 assertions**.
- admin regression after request-context compatibility correction: **24 passed, 114 assertions**.
- document/consultation/revision/progress/class regressions: **155 passed, 23 skipped, 694 assertions**.
- audit and notification integration regression: **20 passed, 66 assertions**.
- PostgreSQL migration apply, rollback, and re-apply: **passed**.
- rolled-back PostgreSQL UPDATE and DELETE probes: **both blocked; no probe rows retained**.
- complete Laravel suite: **440 tests; 416 passed, 24 skipped, 1,814 assertions, 0 failures**.
- `vendor/bin/pint --test`: **passed**.
- `npm run build`: **passed**.
- `php artisan view:cache`: **passed**.
- `php artisan migrate:status`: **passed; Phase 24 migration is Ran**.
- Composer validation via the available local `composer.phar`: **passed**.
- `php artisan route:list --except-vendor`: **passed; 127 application routes, no audit mutation route**.
- `git diff --check`: **passed**.

## Known limitations and external dependencies

- Historical actor context remains null when it was never stored.
- Mature form/signature/defense/evaluation writers remain compatible but are not all mechanically rewritten in Phase 24.
- PostgreSQL supplies database-level append-only protection; non-PostgreSQL tests retain model-level protection, while production also has no mutation endpoint.
- Dependencies are PostgreSQL, Spatie Laravel Permission, Laravel auth/session middleware, and authoritative domain assignments.
- Phase 23's separate Google Drive synchronization status is not changed or invented here.

## Phase 25 boundary and capstone explanation

Phase 25 may build reports/exports from permission-safe queries. Phase 24 intentionally provides neither export nor retention deletion.

NDMU-RMAS now retains complementary evidence: detailed feature tables preserve exact academic facts, while the central audit trail gives a concise institutional timeline across modules. Critical entries commit with authorized mutations, use server-derived identity/context, redact secrets, and cannot be edited or deleted through the application or PostgreSQL. This provides accountability without creating a conflicting second academic database.

Phase 24 is complete. Strict roadmap progress is **23 / 27 (~85.2%)**, not 24 / 27, because Phase 23's required Google Drive documentation synchronization remains pending.
