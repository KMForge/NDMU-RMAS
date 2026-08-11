# Student and Adviser Backend Flows

This document describes the backend behavior currently implemented for the Student Researcher and Research Adviser areas of NDMU-RMAS. It follows the actual routes, controllers, form requests, actions, policies, queries, models, and migrations in this repository.

Last reviewed: July 31, 2026

## 1. Scope and status labels

The feature tables use these labels:

- **Complete workflow**: database read/write flow, validation, authorization, and response handling are connected.
- **Database-backed read**: the dashboard reads real records, but this role does not yet have a complete create/update endpoint for the feature.
- **UI only**: the tab or control exists, but a dedicated backend workflow has not been implemented.

No static research, document, consultation, class, or revision records are intended to be used by the implemented flows below. Empty database results are rendered as empty states.

## 2. Backend architecture

The normal request path is:

```text
Blade form or JSON client
  -> named Laravel route
  -> authentication/account/role/permission middleware
  -> Form Request validation and authorization
  -> controller
  -> domain action or scoped query
  -> policy/ownership/assignment check
  -> Eloquent or query builder
  -> PostgreSQL (Supabase)
  -> redirect with flash data or a safe JSON response
```

Main locations:

| Responsibility | Location |
|---|---|
| Student routes | [`routes/student.php`](../routes/student.php) |
| Adviser routes | [`routes/adviser.php`](../routes/adviser.php) |
| Shared/auth/document routes | [`routes/web.php`](../routes/web.php), [`routes/auth.php`](../routes/auth.php) |
| Controllers | [`app/Http/Controllers`](../app/Http/Controllers) |
| Validation | [`app/Http/Requests`](../app/Http/Requests) |
| Business workflows | [`app/Modules`](../app/Modules) |
| Record authorization | [`app/Policies`](../app/Policies) |
| Eloquent models | [`app/Models`](../app/Models) |
| Role permissions | [`database/seeders/RolePermissionSeeder.php`](../database/seeders/RolePermissionSeeder.php) |
| Rate limits | [`app/Providers/AppServiceProvider.php`](../app/Providers/AppServiceProvider.php) |

## 3. Common authentication and access flow

### Login

1. A guest submits `POST /login` with `email`, `password`, and optional `remember`.
2. [`LoginRequest`](../app/Http/Requests/Authentication/LoginRequest.php) validates the input.
3. [`AuthenticatedSessionController`](../app/Http/Controllers/Authentication/AuthenticatedSessionController.php) uses `Auth::attempt()`. Laravel/Eloquent issues parameterized database queries and verifies the stored password hash.
4. The account must be active, approved, and assigned at least one recognized Spatie role.
5. The session ID is regenerated after successful authentication.
6. [`UserRole::highestFor()`](../app/Enums/UserRole.php) determines the destination when a user has multiple roles.
7. A student is redirected to `student.dashboard`; an adviser is redirected to `adviser.dashboard`.

Login does not accept a role field. The role is read from the authenticated user's database roles, preventing a client from choosing a more privileged destination.

### Middleware applied to role areas

Every student and adviser route requires:

- `auth`
- verified email
- active and approved account through `active`
- the correct Spatie role
- the role area's base permission

Student routes require `role:student-researcher` and `permission:research.view-own`. Adviser routes require `role:research-adviser` and `permission:research.view-assigned`. Sensitive actions add more permissions and record-scoped policies.

### Logout

`POST /logout` logs out the web guard, invalidates the session, regenerates the CSRF token, and redirects to the welcome page. JSON clients receive a success message.

## 4. Student feature map

| Student feature | Status | Backend source and behavior |
|---|---|---|
| Dashboard overview and search | Database-backed read | [`GetStudentDashboardData`](../app/Modules/Research/Queries/GetStudentDashboardData.php) calculates project progress, tasks, next consultation/defense, document counts, and recent updates. |
| My Classes | Complete workflow | Reads active classes and join-request states; students can request entry using a join code. |
| My Research | Database-backed read | Reads the student's active project, program, team, and adviser from research/group/assignment tables. |
| Research Proposal | Database-backed read | Reads `research_proposals`; document review may synchronize proposal status when the required schema is available. |
| Research Progress | Database-backed group read | Reads weighted `research_group_milestones`; document review decisions never mutate official progress automatically. |
| Consultation Records | Complete workflow | Student books a consultation and sees requests plus completed records. |
| Revision Tracker | Complete workflow | Student starts an assigned revision and submits a secured replacement document. |
| Defense Schedule | Database-backed read | Reads defense requests, schedules, and rooms. No student scheduling endpoint exists yet. |
| Evaluation Results | Database-backed read | Reads evaluations belonging to the student's research project. No student write endpoint exists. |
| Research Repository | Complete workflow | Lists the student's real documents and provides policy-protected view/download links. |
| Notifications | Database-backed read | Reads the latest 25 Laravel database notifications when the table exists. |
| Official Forms | UI only | No dedicated student form-download workflow is present in the current student routes. |
| Settings | UI only | No student settings update endpoint is present in the current student routes. |

## 5. Adviser feature map

| Adviser feature | Status | Backend source and behavior |
|---|---|---|
| Dashboard overview | Database-backed read | [`GetAdviserDashboardOverview`](../app/Modules/Research/Queries/GetAdviserDashboardOverview.php) reads active advisees, urgent reviews, consultations, revisions, defenses, recent activity, and notifications. |
| My Classes | Complete workflow | Adviser creates classes with auto-generated join codes and opens an owned class to see active students. |
| Join Requests | Complete workflow | Adviser searches/filters requests for owned classes and approves or rejects each pending request. |
| Assigned Researchers | Database-backed summary | Active enrolled students are included in adviser dashboard aggregates; there is no separate mutation endpoint. |
| Proposal Review | Part of document review | Review decisions may synchronize a `research_proposals` record when the integration tables/columns exist. |
| Research Monitoring | Database-backed summary | Dashboard progress data comes from real research projects and progress updates; no dedicated adviser progress-write route exists yet. |
| Consultation Records | Complete workflow | Adviser sees assigned requests, approves/rejects them, and records completed consultations. |
| Document Review | Complete workflow | Adviser sees only reviewable documents, previews/downloads, comments, resolves comments, and records decisions. |
| Revision Management | Complete workflow | Revision requests created by the adviser can be searched, resolved, or reopened. |
| Defense Endorsement | Database-backed summary | Defense counts can appear in the overview; a dedicated adviser endorsement endpoint is not present. |
| Evaluation Records | Database-backed summary | Assigned evaluation information may be read by existing queries; a dedicated adviser write endpoint is not present. |
| Research Repository | Complete workflow | Adviser searches/filters authorized real documents, securely views/downloads them, and can upload adviser-owned documents. |
| Notifications | Database-backed read | Uses Laravel database notifications when the table exists. |
| Official Forms | UI only | No dedicated adviser official-form endpoint is present. |
| Settings | UI only | No adviser settings update endpoint is present. |

## 6. Student route reference

All routes below inherit the student middleware described in Section 3.

| Method and URI | Route name | Additional protection | Result |
|---|---|---|---|
| `GET /student/dashboard` | `student.dashboard` | — | Returns the database-backed student dashboard. |
| `POST /student/documents` | `student.documents.store` | 5 uploads/minute | Stores a validated private document. |
| `POST /student/consultations` | `student.consultations.store` | 5 bookings/hour | Creates a pending consultation request. |
| `POST /student/classes/join` | `student.classes.join` | 10 attempts/minute | Creates or renews a pending class join request. |
| `PATCH /student/revisions/{revisionRequest}/start` | `student.revisions.start` | 30 revision actions/minute; revision policy | Changes `open` to `in_progress`. |
| `POST /student/revisions/{revisionRequest}/documents` | `student.revisions.submit` | Revision and upload limits; revision policy | Stores a revised document and changes the request to `submitted`. |

## 7. Adviser route reference

All routes below inherit the adviser middleware described in Section 3.

| Method and URI | Route name | Additional protection | Result |
|---|---|---|---|
| `GET /adviser/dashboard` | `adviser.dashboard` | Scoped queries | Loads only the selected tab's expensive dataset where possible. |
| `POST /adviser/classes` | `adviser.classes.store` | 10 creations/hour | Creates an owned class and secure join code. |
| `GET /adviser/classes/{researchClass}` | `adviser.classes.show` | `ResearchClassPolicy::view` | Shows active students only for the owning adviser. |
| `PATCH /adviser/classes/{class}/join-requests/{request}/approve` | `adviser.classes.join-requests.approve` | 30 decisions/minute; owner policy | Activates a pending enrollment if capacity remains. |
| `PATCH /adviser/classes/{class}/join-requests/{request}/reject` | `adviser.classes.join-requests.reject` | 30 decisions/minute; owner policy | Rejects a pending enrollment. |
| `PATCH /adviser/consultations/{request}/approve` | `adviser.consultations.approve` | `consultations.manage-assigned`; 30/minute; policy | Approves an assigned pending request. |
| `PATCH /adviser/consultations/{request}/reject` | `adviser.consultations.reject` | Same as approve | Rejects an assigned pending request. |
| `POST /adviser/consultations/{request}/complete` | `adviser.consultations.complete` | Same as approve | Creates a consultation record and marks the request completed. |
| `POST /adviser/documents/{document}/comments` | `adviser.documents.comments.store` | `documents.review`; 60/minute; document scope | Adds a sanitized comment to a reviewable document. |
| `PATCH /adviser/documents/{document}/comments/{comment}/resolve` | `adviser.documents.comments.resolve` | Same as comments | Resolves a comment belonging to that document. |
| `PATCH /adviser/documents/{document}/review` | `adviser.documents.review` | Same as comments | Accepts, requests revision, or rejects a document. |
| `PATCH /adviser/revisions/{request}/resolve` | `adviser.revisions.resolve` | `revisions.resolve`; 30/minute; revision policy | Changes a submitted revision to resolved. |
| `PATCH /adviser/revisions/{request}/reopen` | `adviser.revisions.reopen` | Same as resolve | Reopens a submitted/resolved revision. |
| `POST /adviser/repository/documents` | `adviser.repository.documents.store` | `documents.upload`; 5/minute | Stores a validated adviser-owned private document. |

Shared document routes are `GET /documents/{document}/view` and `GET /documents/{document}/download`. Both require an authenticated, verified, active account and a successful `DocumentPolicy` check.

## 8. Class and join-request flow

### Adviser creates a class

1. [`CreateResearchClassRequest`](../app/Http/Requests/Classes/CreateResearchClassRequest.php) requires a UUID `creation_token`, a 3–120 character name, optional description up to 1,000 characters, and a student limit from 1–100.
2. [`CreateResearchClass`](../app/Modules/Classes/Actions/CreateResearchClass.php) obtains a cache lock using the adviser ID and creation token.
3. The token is checked against existing classes to prevent a double-click from creating duplicates.
4. An eight-character uppercase alphanumeric join code is generated.
5. Only the SHA-256 fingerprint is used for code lookup. The encrypted code can be revealed to the owning adviser through the model.
6. The class is inserted in a transaction as active.

### Student requests to join

1. [`JoinResearchClassRequest`](../app/Http/Requests/Classes/JoinResearchClassRequest.php) normalizes and validates the join code.
2. [`RequestToJoinResearchClass`](../app/Modules/Classes/Actions/RequestToJoinResearchClass.php) finds and locks the active class by code fingerprint.
3. It rejects duplicate pending/active membership and checks the class capacity.
4. A new or previously rejected enrollment is saved as `pending`.
5. The request appears in the owning adviser's Join Requests tab, not in the active student list.

### Adviser decides

1. `ResearchClassPolicy` verifies the adviser owns the class and has `classes.manage-join-requests`.
2. [`ReviewResearchClassJoinRequest`](../app/Modules/Classes/Actions/ReviewResearchClassJoinRequest.php) locks both class and request.
3. Only `pending` requests can be decided.
4. Approval rechecks capacity and sets `status=active`, `joined_at`, `reviewed_by`, and `reviewed_at`.
5. Rejection sets `status=rejected` and the review metadata.

Status flow:

```text
no request -> pending -> active
                      -> rejected -> pending (student may request again)
```

## 9. Consultation flow

### Student books

1. [`BookConsultationRequest`](../app/Http/Requests/Consultations/BookConsultationRequest.php) requires a UUID request token, a future date no more than three months ahead, mode `in_person` or `online`, and a sanitized 10–2,000 character agenda.
2. [`BookConsultation`](../app/Modules/Consultations/Actions/BookConsultation.php) prevents duplicate processing with a cache lock and unique token.
3. It locates the student's active research project and active adviser assignment.
4. It creates a `pending` `consultation_requests` record in a transaction.

### Adviser reviews and completes

1. `ConsultationRequestPolicy` confirms the request belongs to the adviser's current active assignment.
2. Approve/reject locks the request and only permits a transition from `pending`.
3. The decision stores status, reviewer, review time, and optional sanitized notes.
4. An approved request may be completed with date/time, location or HTTPS/HTTP meeting URL, discussion, recommendations, and optional next schedule.
5. Completion inserts `consultation_records` and changes the request to `completed` in the same transaction.

Status flow:

```text
pending -> approved -> completed
        -> rejected
```

## 10. Secure document upload flow

The student Submit Document form, student revision upload, and adviser repository upload reuse the same secure upload action.

1. Route middleware verifies account, role, permission, and rate limit.
2. [`StoreDocumentRequest`](../app/Http/Requests/Documents/StoreDocumentRequest.php) or [`StoreRevisionDocumentRequest`](../app/Http/Requests/Revisions/StoreRevisionDocumentRequest.php) requires a UUID `submission_token` and a file no larger than 10 MB.
3. [`SecureDocumentFile`](../app/Modules/Documents/Rules/SecureDocumentFile.php) validates:
   - uploaded-file validity and non-zero size;
   - filename traversal/null-byte checks;
   - extension allowlist: `.pdf` and `.docx` only;
   - MIME type matching the selected extension;
   - PDF `%PDF-` header and `%%EOF` trailer;
   - DOCX ZIP signature and required Office XML entries;
   - archive paths, entry count, expanded size, macros, and embedded dangerous extensions;
   - explicit rejection of executable/script types such as EXE, BAT, CMD, PHP, JS, DLL, APK, and related formats.
4. [`SubmitDocument`](../app/Modules/Documents/Actions/SubmitDocument.php) uses a per-user/token cache lock plus the unique `(user_id, submission_token)` constraint to prevent duplicate submissions.
5. The original filename is sanitized for metadata. A UUID becomes the stored filename.
6. The file is written with private visibility under a user/year/month directory on the configured disk.
7. SHA-256, MIME type, byte size, disk, private path, submitter, time, and `pending` status are saved in `documents`.
8. The database save and upload audit are transactional. If any database or revision step fails, the stored object is deleted.
9. Every successful or failed attempt is written to `document_upload_audits` with user, original filename, IP address, time, outcome, and safe failure reason.
10. Browser responses redirect with flash messages. JSON responses return safe metadata and authorized URLs, never the storage disk, private path, UUID filename, content hash, or stack trace.

The currently configured document disk defaults to Laravel's private local disk at `storage/app/private/documents`. Moving this workflow to Supabase Storage requires configuring a Laravel-compatible private disk (or adapting the action to the existing storage provider); direct public object URLs must not be used.

## 11. Document access, repository, and review flow

### Authorization scope

[`DocumentPolicy`](../app/Policies/DocumentPolicy.php) permits view/download only when the user has `documents.download` and is one of:

- the document owner;
- a user with `documents.download-any`; or
- an authorized reviewer established by [`DocumentReviewerAccess`](../app/Modules/Documents/Support/DocumentReviewerAccess.php).

An adviser can always access adviser-owned repository uploads through the ownership branch of `DocumentPolicy`. Review authority is narrower: it covers documents from students actively enrolled in one of the adviser's classes or students linked through an active adviser assignment. A user with `research.view-all` and `documents.review` receives the wider review scope.

### Student repository

The student dashboard queries `documents.user_id = authenticated student ID`, newest first. View/download always passes through the shared controller and policy; storage details are hidden on the model and in API payloads.

### Adviser repository

[`GetAdviserRepositoryData`](../app/Modules/Documents/Queries/GetAdviserRepositoryData.php) applies the adviser document scope before search, filter, count, or pagination. It supports filename/researcher search and these display filters:

- Approved: `accepted`
- Pending Review: `pending` or `submitted`
- For Evaluation: `under_review`
- Revisions Requested: `revision_requested`
- Rejected: `rejected`

### Adviser review

1. [`GetAdviserDocumentReviewData`](../app/Modules/Documents/Queries/GetAdviserDocumentReviewData.php) returns only reviewable scoped documents and their real comments/statistics.
2. The controller calls `Gate::authorize('review', $document)` before comment, resolve, or decision operations.
3. Comments are stripped of HTML, length-limited, optionally tied to a page, and assigned severity `comment`, `revision`, or `critical`.
4. A first comment moves a pending document to `under_review`.
5. [`ReviewDocument`](../app/Modules/Documents/Actions/ReviewDocument.php) locks the document and rejects repeated terminal decisions.
6. A review row and audit row are created; the document becomes `accepted`, `revision_requested`, or `rejected`.
7. When compatible integration tables are present, the action synchronizes proposal status, creates/reuses a revision request, or records approved progress.
8. The student receives a Laravel database notification when the notifications table exists.

Document status flow:

```text
pending -> under_review -> accepted
                        -> revision_requested
                        -> rejected
```

## 12. Revision flow

A revision request is normally created by an adviser document decision of `revision_requested`. It contains the source document, adviser/requester, assigned student, instructions, due date, and current status.

1. The student sees only requests assigned to that account or linked to the student's research project.
2. `RevisionRequestPolicy::start` and `::submit` verify the authenticated student is `assigned_to` the request and has the required permissions.
3. Starting locks the request and changes `open` to `in_progress`.
4. Submitting reuses the secure upload flow, links the new document through `revision_request_id`, changes the request to `submitted`, writes a `revision_request_events` audit entry, and notifies the adviser.
5. The adviser revision query is scoped to `requested_by = authenticated adviser ID`.
6. `RevisionRequestPolicy::manage` additionally verifies the requester, authorized reviewer scope, or all-research permission.
7. Resolve changes `submitted` to `resolved`. Reopen changes `submitted` or `resolved` to `open`.
8. Every transition uses a transaction, row lock, sanitized notes, validated IP address, event record, and counterparty notification.

Status flow:

```text
open -> in_progress -> submitted -> resolved
 ^                         |           |
 +-------------------------+-----------+  reopen
```

## 13. Dashboard data sources

### Student dashboard

The query uses the authenticated user and, where relevant, active/non-archived membership. It reads from:

- `users`, `student_profiles`
- `research_group_members`, `research_groups`, `programs`
- `research_projects`, `adviser_assignments`, `faculty_profiles`
- `research_proposals`, `milestone_definitions`, `research_group_milestones`, `research_group_milestone_events`, `milestone_evidences`
- `consultation_requests`, `consultation_records`
- `documents`, `revision_requests`
- `defense_requests`, `defense_schedules`, `defense_rooms`
- `evaluations`
- `research_classes`, `research_class_enrollments`
- `notifications`

Optional dashboard sections check whether their tables exist and return empty collections when unavailable. This avoids fabricating fallback records.

### Adviser dashboard

The controller validates the requested tab and loads specialized queries only for the active data-heavy tab:

- `classes`: adviser-owned classes and enrollment counts
- `requests`: owned-class join requests, statistics, search, and status filter
- `consultation`: assigned requests and completed records
- `docreview`: authorized document scope, comments, and review statistics
- `revisions`: adviser-created revision requests
- `repository`: authorized documents, status counts, search, and pagination
- `dashboard`: overview aggregates, advisees, pending documents, consultations, and recent activity

This lazy tab loading reduces unnecessary database work when switching adviser sections.

## 14. Main persistence tables by workflow

| Workflow | Main tables |
|---|---|
| Accounts and authorization | `users`, `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `sessions` |
| Classes | `research_classes`, `research_class_enrollments` |
| Student research identity | `student_profiles`, `research_group_members`, `research_groups`, `programs` |
| Projects and adviser scope | `research_projects`, `faculty_profiles`, `adviser_assignments` |
| Consultations | `consultation_requests`, `consultation_records` |
| Documents | `documents`, `document_upload_audits` |
| Document review | `document_reviews`, `document_review_comments`, `document_review_audits` |
| Revisions | `revision_requests`, `revision_request_events`, plus revised rows in `documents` |
| Proposal/progress integration | `research_proposals`; Phase 18 progress remains separate in `research_group_milestones` and may only link evidence |
| Defense/evaluation reads | `defense_requests`, `defense_schedules`, `defense_rooms`, `evaluations` |
| User notifications | `notifications` |

## 15. Security controls

| Control | Implementation |
|---|---|
| SQL injection | Eloquent/query builder bindings are used; search `LIKE` inputs are bound parameters. |
| CSRF | Web forms use Laravel's CSRF middleware and `@csrf`; PATCH forms also use method spoofing. |
| Authentication/session security | Session regeneration after login; invalidation and CSRF regeneration on logout. |
| Role escalation prevention | Role is taken from Spatie database assignments, never from a login field. |
| Record access | Policies and assignment/ownership scopes supplement route permissions. |
| XSS | Text inputs are stripped/trimmed before storage and Blade escapes normal `{{ }}` output. |
| File security | Allowlisted extension/MIME/signature, DOCX archive inspection, private storage, UUID names, safe access endpoint. |
| Duplicate submissions | UUID idempotency tokens, unique constraints, cache locks, and row locks. |
| Race conditions | Database transactions and `lockForUpdate()` on class, enrollment, document, consultation, and revision transitions. |
| Abuse control | Login 10/minute; uploads 5/minute; bookings 5/hour; class create 10/hour; joins 10/minute; adviser decisions/actions have dedicated limits. |
| Auditing | Upload attempts, document reviews, and revision transitions have dedicated audit/event tables. |
| Information disclosure | Safe errors and JSON payloads omit database errors, stack traces, private paths, generated filenames, hashes, and secrets. |
| Download response hardening | `nosniff`, private/no-store cache headers, same-origin resource policy, and restrictive CSP headers. |

## 16. Browser and JSON behavior

The same workflow endpoints support normal Blade forms and Postman/JSON clients.

- Browser success: redirect to the relevant dashboard tab with a flash message.
- Browser validation failure: redirect back with field errors.
- JSON success: generally `200` for transitions or `201` for created records.
- JSON validation failure: `422`.
- JSON unauthenticated: `401` or login redirect depending on headers/session configuration.
- JSON unauthorized: `403`.
- Duplicate/idempotency conflict: `409`.
- Rate limit exceeded: `429`.
- Unexpected storage/server failure: safe `500` message without internal details.

For JSON testing, send `Accept: application/json`. For session-authenticated web routes, obtain the Laravel session and CSRF cookie/token first; these are not bearer-token API routes.

## 17. Verification tests

Relevant automated coverage is in:

- [`tests/Feature/Authentication/LoginTest.php`](../tests/Feature/Authentication/LoginTest.php)
- [`tests/Feature/Authentication/AccountAccessTest.php`](../tests/Feature/Authentication/AccountAccessTest.php)
- [`tests/Feature/Classes/ResearchClassWorkflowTest.php`](../tests/Feature/Classes/ResearchClassWorkflowTest.php)
- [`tests/Feature/Consultations/ConsultationBookingTest.php`](../tests/Feature/Consultations/ConsultationBookingTest.php)
- [`tests/Feature/Consultations/AdviserConsultationManagementTest.php`](../tests/Feature/Consultations/AdviserConsultationManagementTest.php)
- [`tests/Feature/Documents/DocumentSubmissionTest.php`](../tests/Feature/Documents/DocumentSubmissionTest.php)
- [`tests/Feature/Documents/AdviserDocumentReviewTest.php`](../tests/Feature/Documents/AdviserDocumentReviewTest.php)
- [`tests/Feature/Documents/AdviserRepositoryTest.php`](../tests/Feature/Documents/AdviserRepositoryTest.php)
- [`tests/Feature/Revisions/RevisionWorkflowTest.php`](../tests/Feature/Revisions/RevisionWorkflowTest.php)
- [`tests/Feature/StudentDashboardDataTest.php`](../tests/Feature/StudentDashboardDataTest.php)
- [`tests/Feature/AdviserDashboardOverviewTest.php`](../tests/Feature/AdviserDashboardOverviewTest.php)

Run the backend verification suite with:

```powershell
php artisan test
vendor/bin/pint --test
```

## 18. Current backend gaps and recommended next flows

These items should not be presented as complete backend workflows yet:

1. Student research project/proposal creation and metadata editing.
2. Dedicated progress-update submission and adviser progress approval outside document-review integration.
3. Defense request/endorsement/scheduling actions for student and adviser.
4. Evaluation detail and adviser-facing evaluation actions.
5. Official form catalog/download authorization.
6. Student and adviser profile/settings updates.
7. Dedicated notification read/unread endpoints.

Implement each new flow using the same boundary: role middleware, Form Request, scoped policy, action/service transaction, audit where needed, safe response, and feature tests.
