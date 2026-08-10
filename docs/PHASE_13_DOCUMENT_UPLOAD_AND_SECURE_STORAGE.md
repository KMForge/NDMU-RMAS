# Phase 13 — Document Upload and Secure Storage

## Status
Completed

## Overview
Phase 13 establishes a secure, auditable, research-group-owned document submission workflow for capstone teams in NDMU-RMAS. Submissions are owned by active research groups (`ResearchClassGroup`), uploaded exclusively by designated Group Leaders (`leader_student_id`), and automatically versioned (`CURRENT` vs `VOID`).

## Key Features Implemented

1. **Research Group Document Ownership & Group Leader Authorization**
   - Added `leader_student_id` (nullable foreign key to `users.id`) on `research_class_groups`.
   - Added `research_class_group_id`, `version_number` (default 1), and `is_current` (default `true`) to `documents`.
   - Replaced individual-student ownership with research-group ownership while retaining uploader identity in `user_id` for accountability.
   - Only the designated active Group Leader can submit research documents. Non-leaders, unassigned students, or members of disbanded groups are blocked server-side.
   - `research_class_group_id` is the authoritative ownership link. `user_id` records the individual uploader and does not make that user the sole owner.
   - Active members of the owning group can be recognized independently of which member uploaded the file. Changing the Group Leader does not transfer or invalidate document ownership.

2. **Group Leader Management**
   - `AssignResearchClassGroupLeader` enables the Research Facilitator to assign or change the Group Leader from valid members of an active research group.
   - Moving a current leader to another group or disbanding the group clears stale leader assignment so upload authorization cannot remain attached to a student who is no longer eligible.
   - Current UI shows the designated Group Leader distinctly in facilitator, adviser, and student group-related views where that relationship is loaded.

3. **Secure Storage & Allowed Files**
   - Accepts PDF and DOCX only, up to 10 MB (`10240` KB).
   - `SecureDocumentFile` checks extension/MIME consistency, dangerous double extensions, PDF structure, DOCX ZIP structure, traversal paths, dangerous embedded content, and macro-enabled content.
   - Files are stored privately using UUID-generated physical filenames under the configured private document disk/directory structure.
   - SHA-256 is calculated server-side for stored content.

4. **Exact Duplicate File Rejection & Version Lifecycle**
   - Exact duplicate SHA-256 submissions against the current group document are rejected rather than generating meaningless duplicate records.
   - A successful replacement is committed transactionally with row locking.
   - The former current record is preserved and becomes `is_current = false` (`VOID`), while the new record becomes `is_current = true` (`CURRENT`) with an incremented version number.
   - VOID means superseded historical version; it does not mean physical deletion.

5. **Audit Logging**
   - Successful and failed upload attempts are recorded in `document_upload_audits` with uploader and Research Class Group context where available.
   - File storage and database persistence are coordinated so a failed metadata transaction does not intentionally leave an orphaned newly stored file.

6. **Student Submission UI**
   - The upload workflow is available only to the Group Leader; non-leaders receive a clear server-backed restriction.
   - Current and prior versions are exposed in the Phase 13 submission context using CURRENT/VOID labels.
   - Internal storage metadata such as private path, stored UUID filename, SHA-256, and submission token is not intended for normal user display.

## Latest Verified Refinement — Commit `ab529a5c0ba722718797d43ff49aa69e333c2bc2`

The latest verified commit does **not** create a new backend phase. It refines the completed Phase 12/13 user experience and keeps repository responsibilities separated from submission responsibilities.

### Student upload location
- `Student\DocumentController` now redirects successful browser uploads and upload errors back to `student.dashboard?tab=proposal` rather than the generic dashboard.
- The Student Dashboard no longer exposes an Upload Document shortcut inside the Research Repository section.
- The submission UI is therefore associated with the Research Proposal/submission workspace, while the Research Repository remains conceptually focused on secure browsing/viewing/downloading work that belongs to Phase 14.

### Group Leader visibility
- Student class details now load the group's `leader` relationship together with adviser and member data.
- Adviser assigned-group cards visually distinguish the current Student Leader from ordinary group members.
- Facilitator group management more clearly distinguishes Assign Group Leader versus Change Group Leader and marks the current leader in the member list.

### Regression-test additions
The same commit updates existing Phase 12/13 tests to verify the revised Group Leader presentation and to ensure the Student Dashboard does not expose a document-upload shortcut outside the Research Proposal area.

## Backend Flow

### Group Leader submission
Student Group Leader
→ protected Student route
→ `StoreDocumentRequest`
→ permission and file validation
→ `Student\DocumentController::store`
→ `SubmitDocument`
→ authoritative active group + current leader revalidation
→ secure private file storage
→ SHA-256 computation
→ duplicate/version checks
→ transaction and row locking
→ previous CURRENT record becomes VOID when replaced
→ new Document metadata saved
→ upload audit recorded
→ browser returns to `student.dashboard?tab=proposal` or JSON response is returned.

### Authorization boundary
Being able to read a group-owned document is separate from being able to upload a new one. Phase 13 keeps upload authority restricted to the current designated Group Leader. Phase 14 owns the broader repository access rules.

## Important Files

- `app/Http/Controllers/Student/DocumentController.php`
- `app/Http/Requests/Documents/StoreDocumentRequest.php`
- `app/Modules/Documents/Rules/SecureDocumentFile.php`
- `app/Modules/Documents/Actions/SubmitDocument.php`
- `app/Modules/Documents/Actions/RecordDocumentUploadAttempt.php`
- `app/Modules/Documents/Support/DocumentFilenameSanitizer.php`
- `app/Models/Document.php`
- `app/Models/DocumentUploadAudit.php`
- `app/Models/ResearchClassGroup.php`
- `app/Modules/Classes/Actions/AssignResearchClassGroupLeader.php`
- `resources/views/pages/student-dashboard.blade.php`
- `resources/views/pages/facilitator-class-details.blade.php`
- `resources/views/pages/adviser-dashboard.blade.php`
- `tests/Feature/Documents/DocumentSubmissionTest.php`
- `tests/Feature/Classes/ResearchClassGroupWorkflowTest.php`

## Verification Evidence

The Phase 13 implementation documentation records:
- `tests/Feature/Documents/DocumentSubmissionTest.php`: 17 tests and 101 assertions passing at Phase 13 completion.
- `vendor/bin/pint`: successful formatting verification.
- `npm run build`: successful frontend build.

The latest `ab529a5c` commit adds/adjusts regression assertions around submission placement and Group Leader presentation. This documentation update does **not** invent a new aggregate pass count for that commit because no new terminal output was independently available through the repository connector.

## Phase Boundary

Phase 13 includes secure upload/storage/versioning/auditing and leader-only submission. It does not by itself complete:
- repository listing/search/filter/pagination;
- secure global document view/download;
- adviser document review decisions/comments;
- progress milestone tracking.

Those remain separate later phases.

## Legacy Compatibility

Documents created before Phase 13 may have a null `research_class_group_id`. Access for those rows uses a deliberately narrow compatibility path based on the original uploader or verified legacy adviser assignment. Group-linked Phase 13 documents do not fall back to uploader ownership.
