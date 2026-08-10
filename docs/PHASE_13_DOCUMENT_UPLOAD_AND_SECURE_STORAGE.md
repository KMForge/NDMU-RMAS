# Phase 13 — Document Upload and Secure Storage

## Status
Completed

## Overview
Phase 13 establishes a secure, auditable, research-group-owned document submission workflow for capstone teams in NDMU-RMAS. Submissions are owned by active research groups (`ResearchClassGroup`), uploaded exclusively by designated Group Leaders (`leader_student_id`), and automatically versioned (`CURRENT` vs `VOID`).

## Key Features Implemented

1. **Research Group Document Ownership & Group Leader Authorization**:
   - Added `leader_student_id` (nullable foreignId -> `users.id`) on `research_class_groups`.
   - Added `research_class_group_id`, `version_number` (default 1), and `is_current` (default `true`) to `documents`.
   - Replaced individual student document ownership with group-level ownership while retaining uploader identity (`user_id`).
   - Only the designated active Group Leader can submit research documents. Non-leaders, unassigned students, or members of disbanded groups are blocked server-side with a safe error message ("Only your assigned Group Leader can submit research documents.").

2. **Group Leader Management**:
   - `AssignResearchClassGroupLeader` action enables Research Facilitators to assign or update the Group Leader for any active research group (validating active class group membership).
   - Moving a student to another group or disbanding a group automatically clears `leader_student_id` on the affected group.

3. **Secure Storage & Allowed Files**:
   - Validates PDF and DOCX files up to 10 MB (`10240` KB) using `SecureDocumentFile` (checking magic bytes, PDF structure `%PDF-`/`%%EOF`, DOCX ZIP structure, macro rejection, path traversal prevention, and dangerous double-extension checks).
   - Stores files privately under `documents/groups/{group_id}/{year}/{month}/{random_uuid_filename}` with SHA-256 hash calculation.

4. **Exact Duplicate File Rejection & Version Lifecycle**:
   - Exact duplicate SHA-256 submissions against a group's current active document are rejected with HTTP 409 Conflict ("This exact file has already been submitted for your research group.").
   - Successful new document submissions within a DB transaction with row locking set prior group documents to `is_current = false` (`VOID`) and assign `is_current = true` (`CURRENT`) to the new document with incremented `version_number`.

5. **Audit Logging & UI Integration**:
   - All success and failure upload attempts are audited in `document_upload_audits` with research group context.
   - Facilitator class detail view includes Group Leader badges and leader assignment controls.
   - Student dashboard includes active group header, Group Leader submission card, non-leader warning banner, and version history listing (`CURRENT` / `VOID`).

## Verification
- Feature test suite in `tests/Feature/Documents/DocumentSubmissionTest.php` passing 100% with 17 tests and 101 assertions.
- Code style formatted with `vendor/bin/pint`.
- Frontend assets compiled with `npm run build`.
