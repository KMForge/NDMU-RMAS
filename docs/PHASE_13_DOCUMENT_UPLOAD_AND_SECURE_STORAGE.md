# Phase 13 — Document Upload and Secure Storage

## Status
Completed

## Overview
Phase 13 establishes the secure, auditable, research-group-owned document submission workflow for NDMU-RMAS. Documents belong to a `ResearchClassGroup`, the individual uploader is retained in `documents.user_id` for accountability, and normal submissions are restricted to the group's designated active Group Leader.

Phase 14 later extended this completed upload foundation by adding an authoritative `document_stage` and making versioning stage-specific. This documentation therefore describes both the original Phase 13 security/storage behavior and the current verified submission contract after the Phase 14 compatibility refinement.

## Core Phase 13 Rules

### Research Group ownership
- `documents.research_class_group_id` is the authoritative modern ownership relationship.
- `documents.user_id` records who physically uploaded the file and is not the sole ownership rule.
- Changing the Group Leader does not transfer or invalidate existing document ownership.

### Group Leader authorization
- `research_class_groups.leader_student_id` identifies the current designated Group Leader.
- Only the valid active Group Leader may create a normal group research submission.
- Non-leaders, students outside the active group, students with inactive class enrollment, and members of disbanded groups cannot upload.
- The Group Leader is a contextual group position, not a global Spatie role.

### Group Leader management
- `AssignResearchClassGroupLeader` allows the owning Research Facilitator to select a valid current group member as leader.
- Moving the current leader to another group or disbanding the group clears stale leader assignment.
- Facilitator, adviser, and student group views identify the designated Group Leader where that relationship is loaded.

## Allowed Files and Secure Validation

Normal research submission accepts only:
- PDF
- DOCX

Maximum upload size is 10 MB (`10240` KB by current configuration).

`SecureDocumentFile` performs server-side checks that include the verified Phase 13 protections:
- uploaded-file validity and non-empty content;
- extension allowlisting;
- MIME/extension consistency;
- dangerous double-extension detection;
- PDF signature/structure checks (`%PDF-` and end-of-file structure);
- DOCX ZIP structure validation;
- ZIP path traversal protection;
- dangerous embedded-extension protection;
- archive entry/uncompressed-size limits;
- macro-enabled Office content rejection.

The browser-provided extension alone is never treated as sufficient proof that the file is safe.

## Private Storage

Files are stored through the configured private document disk rather than a publicly addressable path.

The physical stored filename is generated server-side using a UUID-oriented name. The sanitized original filename is retained only as user-facing metadata.

Important internal fields such as `storage_path`, `storage_disk`, `stored_filename`, `content_sha256`, and the submission token are not intended to be exposed through ordinary user-facing responses.

## SHA-256 and Duplicate Protection

The server calculates SHA-256 for the stored file.

Exact duplicate content is rejected rather than generating meaningless duplicate versions. Duplicate detection is a security/data-integrity control and does not expose the hash to the user.

## Submission Token / Idempotency

`submission_token` is a UUID validated server-side and participates in duplicate-request protection. The submission action uses locking and database checks so repeated browser submission does not silently create the same logical upload twice.

## Current Stage-Aware Submission Contract

Phase 14 added `DocumentStage` and requires every new normal submission to use one of these controlled stages:

1. `title_proposal` — Title Proposal
2. `proposal_defense` — Proposal Defense
3. `pre_final_defense` — Pre-Final Defense
4. `final_defense` — Final Defense
5. `final_manuscript` — Final Manuscript

`StoreDocumentRequest` validates the stage through the enum. The stage is not inferred from the filename.

Existing pre-stage records are not silently guessed; a null stage is represented as unclassified until authoritative classification exists.

## CURRENT / VOID Version Lifecycle

The current verified system treats version state separately from review status.

- `is_current = true` → CURRENT
- `is_current = false` → VOID / superseded historical version

After the Phase 14 refinement, replacement/version numbering is scoped by:

`research_class_group_id + document_stage`

Therefore a new Proposal Defense version may VOID the previous Proposal Defense CURRENT version, but it must not VOID the group's current Final Defense or Final Manuscript document.

Example:

- Proposal Defense V1 → VOID
- Proposal Defense V2 → CURRENT
- Final Defense V1 → CURRENT

VOID does not mean deleted. The previous database record and private stored file remain part of the historical record.

## Transaction and Concurrency Protection

The submission action revalidates authoritative group/leader state and performs sensitive version changes inside the existing protected transaction/locking flow.

The current stage-aware flow must preserve these invariants:
- uploader is still the current valid Group Leader when the record is committed;
- version numbers are calculated within the same group/stage stream;
- the previous CURRENT version in that same stream is changed to VOID;
- unrelated document stages remain unchanged;
- a failed metadata transaction does not intentionally leave an orphaned newly stored file.

## Upload Audit

Successful and failed upload attempts are recorded in `document_upload_audits` with the available uploader/group context.

Representative failures include authorization, validation, duplicate submission/content, storage, and other upload-domain failures according to the implemented action/request paths.

## Student Submission Flow

Student Group Leader
→ authenticated / verified / active Student route
→ `StoreDocumentRequest`
→ `documents.upload` authorization and stage/file validation
→ `Student\DocumentController::store`
→ `SubmitDocument`
→ authoritative active group/current-leader revalidation
→ private storage using generated filename
→ SHA-256 calculation
→ duplicate and stage-specific version checks
→ transaction/locking
→ previous CURRENT in same group/stage becomes VOID when applicable
→ new Document record becomes CURRENT
→ upload audit recorded
→ browser returns to `student.dashboard?tab=proposal` or a safe JSON response is returned.

## UI Boundary

Commit `ab529a5c0ba722718797d43ff49aa69e333c2bc2` refined the completed Phase 13 experience:
- successful uploads and upload errors return to the Research Proposal tab;
- the Student Research Repository does not expose a generic upload shortcut;
- Group Leader identity is clearer in facilitator/adviser/student group presentation.

The upload workflow therefore remains associated with the submission/proposal context, while Phase 14 owns repository browsing/view/download behavior.

## Important Files

- `app/Enums/DocumentStage.php` — added later by Phase 14 to make the existing submission flow stage-aware.
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
- relevant Phase 13/14 migrations
- `resources/views/pages/student-dashboard.blade.php`
- `resources/views/pages/facilitator-class-details.blade.php`
- `resources/views/pages/adviser-dashboard.blade.php`
- `tests/Feature/Documents/DocumentSubmissionTest.php`
- Phase 14 repository/regression tests that verify stage-specific submission behavior.

## Verification Evidence

At Phase 13 completion, repository documentation recorded:
- 17 focused document-submission tests;
- 101 assertions;
- zero failures for that focused run;
- successful Pint formatting;
- successful frontend build.

The later Phase 14 regression documentation records a broader Phase 10–14 run of 115 tests executed, 94 passed, 21 intentionally skipped, 496 assertions, and zero failures, together with successful Pint and production frontend build. That broader run verifies the stage-aware Phase 13/14 integration rather than replacing the historical Phase 13 evidence.

## Phase Boundary

Phase 13 is responsible for secure submission/storage and leader-only upload authorization. The later Phase 14 repository layer provides read/browse/search/filter/pagination/version-history/view/download access and access auditing.

Phase 13 does not implement:
- adviser comments or review decisions;
- revision workflow decisions;
- research progress/milestone automation;
- defense/evaluation workflows.

## Legacy Compatibility

Documents created before group ownership may have `research_class_group_id = null`; repository access for those rows uses the deliberately narrow legacy compatibility path documented in Phase 14.

Documents created before `document_stage` existed may have a null stage. The current repository does not guess a stage from the filename or other weak metadata.
