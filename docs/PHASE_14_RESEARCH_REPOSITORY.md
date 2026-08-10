# Phase 14 — Research Repository

## 1. Purpose

Phase 14 provides a secure, read-oriented repository for research papers. Authorized users can browse safe metadata, search, filter, sort, paginate, view PDFs, download PDF/DOCX files, and inspect CURRENT/VOID version history.

## 2. Scope

This phase implements repository access only. Adviser comments, annotations, approval, rejection, revision decisions, notifications, and milestone automation are excluded.

## 3. Business Rules

- A Research Class Group owns modern documents through `documents.research_class_group_id`.
- `documents.user_id` records the uploader and is not the sole ownership rule.
- Only the current active Group Leader may upload.
- Authorized active group members may read their group's documents.
- Verified former members retain read-only access after group disbanding.
- Superseded versions remain as immutable VOID history.

## 4. Document Ownership

Modern ownership follows `documents.research_class_group_id -> research_class_groups.id`. The uploader remains visible for accountability after a leader change. Null-group documents retain the narrow legacy compatibility rules established by the Phase 14 foundation.

## 5. Document Submission Stages

`DocumentStage` restricts submissions to `title_proposal`, `proposal_defense`, `pre_final_defense`, `final_defense`, and `final_manuscript`. The proposal form requires one server-validated value. Existing records are not assigned a guessed stage; null is displayed as `Unclassified` until authoritative classification exists.

## 6. Stage, Review Status, and Version State

Stage identifies the submission stream, review status uses the existing `DocumentStatus`, and version state is CURRENT when `is_current = true` and VOID otherwise. Phase 14 keeps these independent and does not perform review decisions.

## 7. CURRENT/VOID Versioning

A new upload replaces only the CURRENT record in the same Research Class Group and stage. The previous record becomes VOID and remains available. Documents in other stages remain CURRENT.

## 8. Stage-Specific Versioning

Versions are calculated from `research_class_group_id + document_stage`. Each stage starts at version 1. The submission transaction locks the active group before calculating the next version and changing CURRENT state. This avoids a PostgreSQL-only partial index because the verified runtime is MySQL and tests use SQLite.

## 9. Student Access

Active access requires a group-member record, an active linked enrollment, and an active non-disbanded group. Being the uploader does not create broader group access.

## 10. Historical Disbanded-Group Access

Before disbanding deletes active membership rows, `DisbandResearchClassGroup` archives them in `research_class_group_member_histories`. Only a matching authoritative history row for that disbanded group grants read-only historical access. It never authorizes upload or mutation. No previously disbanded groups existed when this schema decision was approved, so lost history was not reconstructed or guessed.

## 11. Adviser Access

An adviser can read documents only for active, non-disbanded groups whose current `adviser_id` matches that adviser.

## 12. Facilitator Access

A facilitator can read documents for groups belonging to Research Classes they own through `research_classes.facilitator_id`.

## 13. Admin and RBAC Access

All access requires `documents.download`. Global administrative access additionally requires `documents.download-any`. Base download permission does not bypass record scope. Panelist and broad dean access remain deferred because no authoritative assignment relationship was verified.

## 14. Search

Search is limited to the original filename, stripped of HTML, trimmed, and capped at 100 characters. It uses a bound parameter. Internal names, paths, hashes, and tokens are not searched or displayed.

## 15. Filters

Server-side allowlists validate stage, review status, PDF/DOCX type, Current/All Versions, and Newest/Oldest sort. Malformed values fall back to safe defaults.

## 16. Pagination

The repository returns 10 records per page. `withQueryString()` preserves search and filter state. CURRENT is the default; All Versions includes VOID history.

## 17. Secure View Flow

User -> authenticated/verified/active route -> document binding -> `DocumentPolicy` -> relationship scope -> private-file existence -> access audit -> protected response. PDF uses an inline response. DOCX opens safe metadata instead of being converted to HTML.

## 18. Secure Download Flow

User -> authenticated route -> document lookup -> policy authorization -> private-file existence -> access audit -> attachment response using the safe original filename.

## 19. Private Storage

The trusted disk/path comes only from the authorized Document record. Request values are never concatenated into paths. Responses do not expose `storage_path`, `stored_filename`, SHA-256, submission tokens, or private locations.

## 20. Authorization Flow

`DocumentPolicy` delegates repository read/download decisions to `DocumentRepositoryAccess`, which applies permissions and record-scoped active-member, historical-member, adviser, facilitator, administrator, or narrow legacy rules. Hidden UI controls are never treated as authorization.

## 21. IDOR Protection

Cross-group students, unrelated former members, unrelated advisers/facilitators, and unassigned panelists receive 403 even if they know an ID. Missing numeric records receive 404.

## 22. Audit Flow

After authorization and file existence succeed, `RecordDocumentAccess` writes a `document_access_audits` row with document, user, `viewed`/`downloaded` action, validated IP, bounded user agent, and access time. Failed or missing-file responses do not create a successful-access audit.

## 23. Database Changes

Migration `2026_08_09_000001_create_phase14_repository_foundation` adds nullable `documents.document_stage` plus a repository index, creates `research_class_group_member_histories`, and creates `document_access_audits`. It is reversible and does not relabel or delete existing documents.

## 24. Models and Relationships

`Document` casts its stage, `ResearchClassGroup` exposes archived histories, `ResearchClassGroupMemberHistory` stores former membership, and `DocumentAccessAudit` stores successful protected access.

## 25. Controllers, Actions, and Queries

- `DocumentAccessController`: view, download, and history.
- `SubmitDocument`: stage-specific version transitions in the existing protected transaction.
- `RecordDocumentAccess`: successful-access auditing.
- `GetDocumentRepositoryData`: scoped filters, statistics, and paginator.
- `GetDocumentVersionHistory`: one group/stage history stream.
- Role dashboard controllers: inject repository data for the correct workspace.

## 26. Routes

- `GET /documents/{document}/view` (`documents.view`), 120/minute.
- `GET /documents/{document}/download` (`documents.download`), 60/minute.
- `GET /documents/{document}/history` (`documents.history`), 120/minute.

Routes require authenticated, verified, active users and still perform document policy checks.

## 27. UI Flow

Student, adviser, facilitator, and permitted admin workspaces use the shared `document-repository` component following the existing NDMU design. It displays safe metadata and View, Download, and History only—no edit, delete, or review actions.

## 28. Error Handling

Unauthorized access returns 403, missing records/files return safe 404 responses, unsupported view types return 415, and unexpected storage failures return a generic 500 after internal reporting. Paths, SQL, stack traces, and generated filenames are not disclosed.

## 29. Tests

Tests cover non-uploader access, IDOR, disbanded history, leader changes, adviser/facilitator/admin/panelist scope, stage-specific versions, filters, malformed input, sorting, 10-record pagination with query preservation, PDF/DOCX behavior, download/history, missing files, unknown records, and access audits. The verified Phase 10–14 run executed 115 tests: 94 passed, 21 intentionally skipped, 496 assertions, zero failures.

## 30. Security Controls

Authentication and account middleware, Spatie permissions, document policies, bound queries, allowlisted filters, private storage, trusted paths, safe MIME/response headers, throttles, immutable VOID history, and successful access auditing protect the feature.

## 31. Files Changed

Database, model, policy/support, action, query, controller, route, shared/detail/history view, focused test, and documentation files changed. The implementation handoff groups the exact list by responsibility.

## 32. Phase Boundaries

Phase 14 displays existing review status but does not change it. Uploads do not update research progress or milestones.

## 33. Known Deferred Work

- Phase 15: comments, annotations, review decisions, revisions, and notifications.
- Phase 18: progress/milestone automation.
- Existing null-stage records need authoritative classification if later required.
- Panelist/dean access requires authoritative assignment scope.
- No authoritative selected research-title workflow was found, so UI displays `Research title not yet finalized`.

