# Phase 23 — Notifications

## Purpose and boundary

Phase 23 gives each authenticated NDMU-RMAS user a persistent, recipient-owned inbox for academic workflow updates. Notifications tell a user what happened or what now needs attention. They do not become an academic source of truth and cannot mutate documents, reviews, revisions, consultations, milestones, forms, signatures, defenses, or evaluations.

Implemented scope includes database-backed in-app delivery, recipient listing, unread/read state, unread count, mark-one and mark-all operations, safe navigation, contextual workflow integrations, after-commit delivery, duplicate suppression, a shared UI, and security/integration tests. SMTP, push/Realtime, notification preferences, and Phase 24 audit-log functionality are outside this phase.

## Repository baseline

- Repository: `KMForge/NDMU-RMAS`
- Branch: `main`
- HEAD before implementation: `c6d5a406cd5dece3a39a17a15523e67302ad1126`
- This local HEAD was newer than the prompt's `f3f1c001...` reference and was therefore used.
- `KMForge/NDMU-RMAS-Legacy` and `KMForge/NDMU-RMAS-Rebuild` were not modified.

The worktree already contained in-progress document/panelist/database-documentation changes. Phase 23 was added without resetting or discarding those unrelated changes.

## Existing architecture audited and reused

- `App\Models\User` already uses Laravel `Notifiable`.
- Laravel's standard polymorphic `notifications` table already existed.
- Controllers already queried `unreadNotifications` for sidebar badges.
- Several dashboards contained static/client-only notification panels; these were not treated as authoritative.
- Domain mutations already use action/service classes and database transactions. Dispatch was added to those authoritative actions rather than placing business rules in notification classes.
- `AcademicWorkflowNotification` uses only the `database` channel; Phase 23 does not depend on SMTP.

## Persistence and payload

The standard notification UUID, class type, polymorphic recipient, JSON data, `read_at`, and timestamps are retained. The safe payload contains `event_key`, title, plain-text message, category, deterministic `logical_key`, allowlisted route name and scalar parameters, optional context/acting role/actor display name, source type/id, and occurrence time.

It does not contain passwords, tokens, private file paths, signature image paths, HMAC secrets, raw form payloads, arbitrary HTML, signed URLs, or client-provided redirect URLs.

Migration `2026_08_24_180000_add_recipient_read_index_to_notifications_table.php` adds the reversible composite index:

```text
notifications_recipient_read_created_index
(notifiable_type, notifiable_id, read_at, created_at)
```

This supports recipient inbox and unread-count queries. It was applied successfully to the configured local PostgreSQL database.

## Routes and middleware

| Method | Route | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/notifications` | `notifications.index` | Paginated recipient inbox |
| GET | `/notifications/unread-count` | `notifications.unread-count` | Current user's unread count JSON |
| GET | `/notifications/{uuid}/open` | `notifications.open` | Mark owned row read and resolve a safe destination |
| PATCH | `/notifications/{uuid}/read` | `notifications.read` | Mark one owned row read |
| PATCH | `/notifications/read-all` | `notifications.read-all` | Mark only the current user's unread rows read |

All routes use `auth`, `verified`, `active`, and `throttle:120,1`. There is no browser endpoint for creating academic notifications, so a client cannot submit a recipient, role, actor context, email, or action URL.

## Components and flow

`NotificationController` owns list/count/read/read-all/open operations. UUID lookups run through the authenticated user's `notifications()` relation, so cross-user UUIDs return 404.

`GetNotificationsForUser` validates the filter, orders newest first, and paginates 20 rows.

`WorkflowNotificationDispatcher` receives server-derived `User` models, generates a SHA-256 logical key from event/source/recipient/occurrence, registers `DB::afterCommit()` inside transactions, reloads the recipient, skips inactive/unapproved accounts, suppresses an existing logical key, filters route parameters, and writes a database-only notification.

`NotificationDestinationResolver` accepts only named routes configured in `config/notifications.php`. Missing, malformed, or unknown destinations fall back to the inbox. Destination controllers still enforce their normal permission, policy, ownership, assignment, and scope checks.

## Verified event matrix

| Domain | Trigger | Event key(s) | Server-derived recipient | Destination |
| --- | --- | --- | --- | --- |
| Class join | submit request | `class.join-request.submitted` | owning facilitator only | Join Requests |
| Class join | approve/reject | `class.join-request.approved`, `.rejected` | requesting student only | Student Classes |
| Adviser | invite/direct assign | `adviser.invitation.received`, `adviser.assignment.created` | exact invited/assigned adviser | Adviser Classes |
| Adviser | respond/cancel/remove | `adviser.invitation.accepted`, `.declined`, `.cancelled`, `adviser.assignment.removed` | requesting facilitator or affected adviser | Contextual class workspace |
| Documents | title submission/screening | `document.title-proposal.submitted` and decision variants | owning facilitator or exact group members | Screening / Proposal |
| Documents | normal/revision submission | `document.submitted`, `revision.document.submitted` | current assigned adviser | Document Review |
| Review | feedback/decision | `document.feedback.posted`, `document.review.decision-recorded` | exact active group members | Revisions / Proposal |
| Revisions | resolve/reopen | `revision.resolved`, `revision.reopened` | exact group members | Revisions |
| Consultations | request | `consultation.requested` | current assigned adviser | Adviser Consultations |
| Consultations | approve/reject/reschedule/cancel/complete | consultation transition event variants | requester, adviser, or exact group members according to the transition | Role-specific consultations |
| Progress | explicit transition | `research.milestone.updated` | exact members and current adviser, excluding actor | Progress/Monitoring |
| Official forms | actor assignment | `official-form.action-required` | exact assigned form actor | Authorized form instance |
| Defense | schedule/reschedule/cancel | `defense.scheduled`, `.rescheduled`, `.cancelled` | exact group, adviser where useful, and panel roster | Role-specific schedule |
| Defense | panel add/remove | panel assignment/removal event variants | exact added/removed panel users | Panelist Schedule |
| Evaluation | round open | `evaluation.round.opened` | exact frozen panel roster | Proposal/Final Evaluation |
| Evaluation | result release | `evaluation.results.released` | exact group members | Student Evaluations |

Events were added only where an authoritative mutation and recipient relationship were verified. Future or inferred actors were not invented.

## Multi-role Faculty

Recipient selection uses contextual IDs and assignments such as `facilitator_id`, `adviser_id`, group membership, requester, official-form actor assignment, defense panel assignment, and evaluation snapshot. It never broadcasts to everyone who merely has the same role.

A Faculty account with multiple Spatie roles receives one account-level notification with context such as `Acting as Thesis Adviser` and the group name. Removing a role does not rewrite historical text, but opening the destination always requires current authorization.

## Read/unread UI lifecycle

- New notifications start with `read_at = null`.
- The shared page supports All, Unread, and Read filters and visually distinguishes unread rows.
- Mark-one and open are owner-scoped; mark-all updates only the current user's relation.
- Opening marks the row read before resolving its safe internal destination.
- Student, Adviser, Facilitator, Panelist, Dean, and Admin notification links/bells open the persistent center.
- Existing server-derived sidebar badge counts remain the unread indicator.
- The list is paginated and does not load large domain graphs.

## Transaction, duplicate, and security controls

- `DB::afterCommit()` prevents a notification claiming success when its domain transaction rolls back.
- Deterministic logical keys suppress repeated delivery for the same event/source/recipient/occurrence; domain locks and uniqueness remain the primary double-click protection.
- Authentication, verification, active/approved checks, throttling, and relationship-scoped UUID lookup block unauthorized access and IDOR.
- The browser cannot spoof recipients or actor context because no creation endpoint exists.
- Blade escapes payload text and no raw HTML is rendered.
- Persisted destinations are allowlisted internal route names, not arbitrary URLs.
- Eloquent/parameterized queries are used.
- Notification code observes domain transitions and does not govern them.

## Tests and actual verification

Focused file `tests/Feature/Notifications/NotificationCenterTest.php` verifies guest denial, unverified/inactive denial, list/count/read isolation, cross-user UUID 404, read-all isolation, safe destination fallback, idempotency, sensitive payload exclusion, rollback safety, absence of a spoofable creation endpoint, and exact class-join recipients.

Existing Research Progress and Revision tests were updated to assert Phase 23 event keys and exact contextual recipients instead of the superseded pre-Phase-23 “no notifications” boundary.

| Gate | Actual result |
| --- | --- |
| Focused Phase 23 | PASS — 8 tests, 32 assertions |
| Full `php artisan test` | PASS — 428 tests; 404 passed, 24 skipped; 1,780 assertions; 0 failures |
| `vendor/bin/pint --test` | PASS |
| `npm run build` | PASS |
| `php artisan view:cache` | PASS |
| notification route inspection | PASS — 5 routes |
| migration | PASS — applied |
| `composer validate` | PASS — `composer.json is valid`; checksum-verified temporary official Composer PHAR removed afterward |
| `git diff --check` | PASS |

## Files changed and why

Added: `NotificationController`, `AcademicWorkflowNotification`, the notification query/dispatcher/resolver, notification index migration, shared inbox Blade view, focused feature tests, and this document.

Modified: `routes/web.php`, `config/notifications.php`, authoritative class/adviser/document/review/revision/consultation/progress/form/defense/evaluation actions, all workspace notification links, and superseded progress/revision notification assertions.

## Known limitations and Phase 24 boundary

- Database delivery is complete and independent of SMTP.
- Email, browser push, Supabase Realtime, and preferences are not implemented.
- Logical-key uniqueness is application-level because the standard payload is JSON; authoritative domain constraints prevent duplicate state transitions.
- Historical notifications do not preserve access; lost authorization fails at the destination.
- Notifications answer what a user needs to know. Phase 24 Audit Logs remains responsible for who performed significant actions and when.

## Definition of Done

Phase 23 satisfies persistent ownership, unread/read lifecycle, unread count, safe navigation, core contextual workflow events, multi-role behavior, IDOR/spoof protection, rollback safety, duplicate suppression, shared UI, focused and regression tests, formatting, build, Blade compilation, migration validation, Composer validation, route inspection, and repository documentation. The attempted Google Drive synchronization was rejected by the connected Drive tool, so the official roadmap remains at 22 / 27 until that final documentation gate succeeds.
