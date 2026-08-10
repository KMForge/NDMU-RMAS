# Phase 14 — Research Repository Foundation

## Status

In Progress — ownership and access foundation corrected; full repository functionality remains disabled.

## Purpose

This correction aligns document authorization and repository queries with the Phase 13 ownership model:

- `documents.research_class_group_id` identifies the owning Research Class Group.
- `documents.user_id` identifies the individual uploader for history and accountability.
- Upload authorization remains limited to the active Group Leader.

This work does not activate repository view/download routes, adviser uploads, document search, status filters, or pagination.

## Authorization Foundation

### Active group members

A student is recognized as an authorized member of a group-owned document only when all of the following are true:

- the document has a `research_class_group_id`;
- a matching `research_class_group_members` row exists for the student;
- the owning `research_class_groups` row is active and not disbanded; and
- the linked `research_class_enrollments` row is active.

The shared `DocumentGroupAccess` service owns this check. The document uploader does not receive group-document access merely because `documents.user_id` matches.

### Assigned adviser

Current adviser access follows the direct Phase 12/13 relationship:

`documents.research_class_group_id → research_class_groups.id → research_class_groups.adviser_id`

The group must be active and not disbanded. An adviser assigned to another group is denied.

### Administrative access

The existing `documents.download-any` permission is preserved. The policy still requires `documents.download`; having only `documents.download` always requires record-scoped authorization.

## Student Query Foundation

Student dashboard counts, recent document results, dashboard document search, and the repository collection now query documents through the student's authoritative active Research Class Group. They no longer use `Document::whereBelongsTo($user)`.

The two existing view variables remain intentionally separate:

- `documents` is the tab-specific/dashboard collection used by dashboard summaries and the repository shell.
- `groupDocuments` is the complete version history used by the Phase 13 submission card.

Both are now derived from the same group-scoped base query, preventing conflicting ownership rules while preserving current Blade dependencies.

## Legacy and Null-Group Compatibility

The fallback based on `student_profiles`, `research_group_members`, `research_projects`, `adviser_assignments`, and `faculty_profiles` is classified as **LEGACY COMPATIBILITY**. Existing supported models, records, and deferred review workflows still reference it.

The fallback is restricted to documents where `research_class_group_id` is null. A group-owned document cannot fall through to uploader-based legacy authorization.

For a null-group document:

- its uploader retains narrow record-scoped access when they have `documents.download`; and
- a reviewer may qualify only through a verified active legacy adviser assignment.

## Existing Adviser Test Classification

- Direct adviser scoping by the owning active group is a valid Phase 14 foundation test and has been rewritten accordingly.
- Adviser repository search/status filtering is a later full Phase 14 feature test and is not active in this correction.
- Generic adviser repository upload is stale under the confirmed Phase 13 rules. It was replaced with a test proving the endpoint remains disabled.
- Adviser comments, review decisions, and related UI queue behavior belong to Phase 15 and are not activated here.

## Routes Intentionally Disabled

- `GET /documents/{document}/view`
- `GET /documents/{document}/download`
- `POST /adviser/repository/documents`
- adviser document comments and review decisions

These routes continue to use `DisabledFeatureController` until their corresponding phase is implemented.

## Security Verification

The foundation tests cover:

- same-group access when a different member uploaded the document;
- cross-group IDOR denial;
- assigned adviser access and denial for another adviser;
- continued group ownership after a Group Leader change;
- uploader/group-member separation;
- narrow null-group legacy access; and
- adviser repository upload remaining disabled.

All queries use Eloquent or Laravel's query builder with bound parameters.

## Remaining Business Decisions

- **NEEDS USER CONFIRMATION:** whether students retain repository access to documents after a group is disbanded.
- **NEEDS USER CONFIRMATION:** whether the default repository view shows only CURRENT documents or includes VOID version history.
- **NEEDS USER CONFIRMATION:** exact student-facing visibility, search, filter, and pagination behavior for the full repository.
- **NEEDS VERIFICATION:** whether legacy null-group records will be migrated to Research Class Groups or retained indefinitely.
