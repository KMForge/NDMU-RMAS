# NDMU-RMAS Development Progress Tracker

Notre Dame of Marbel University Research Management and Assistance System (NDMU-RMAS)

This tracker records the backend rebuild progress after the baseline reset. GitHub is the source of truth for code history, while this document summarizes the project phases for capstone tracking and reporting.

## Current Baseline

| Item | Status |
| --- | --- |
| Active repository | `KMForge/NDMU-RMAS` |
| Protected repositories | `KMForge/NDMU-RMAS-Legacy`, `KMForge/NDMU-RMAS-Rebuild` |
| Rebuild baseline commit | `f701324 chore: disable feature backends for rebuild baseline` |
| Latest user implementation commit reviewed | `ab529a5 feat: implement research class group workflow including group management, student assignments, and adviser request system` |
| Phase 14 foundation commit | `a8320f9 feat: implement document group access and update policies for research class group ownership` |
| Phase 14 implementation commit | `173e8f2 feat: Implement Phase 14 Research Repository` |
| Latest completed feature phase | `Phase 14 — Research Repository` |
| Current active feature phase | `Phase 15 — Adviser Document Review (not started)` |
| Backend strategy | Rebuild feature modules one at a time |
| UI strategy | Keep existing UI while reconnecting verified backend modules |

> **Baseline note:** Commit `f701324` remains the functional rebuild baseline. Phases 10 through 14 are complete. Phase 13 implemented secure Group Leader submission; `a8320f9` corrected the Phase 14 group-ownership/access foundation; `ab529a5` refined Research Class Group and Group Leader presentation; and `173e8f2` completed the secure Research Repository.

## Phase Tracker

| Phase | Module | Status | Notes |
| --- | --- | --- | --- |
| Phase 1 | Authentication & Account Access | Completed | Login, logout, session handling, account status checks, and dashboard redirects are active. |
| Phase 2 | Registration Foundation | In Progress | Registration UI exists; backend flow still requires completion/verification before this phase can be marked complete. |
| Phase 3 | User Management | Completed | Admin user management is active. |
| Phase 4 | Dynamic RBAC with Spatie | Completed | Roles, permissions, role assignment, and permission-based access are active. |
| Phase 5 | Admin Dashboard & System Settings | Completed | Admin dashboard shell, user metrics, and system settings are active. |
| Phase 6 | Student Dashboard UI Shell | Completed | UI shell is available with standardized sidebar grouping and feature banners. |
| Phase 7 | Adviser Dashboard UI Shell | Completed | UI shell is available. My Classes integrates the completed Phase 12 adviser-request and assigned-group workflow. |
| Phase 8 | Facilitator Dashboard UI Shell | Completed | UI shell is available and supports the rebuilt Phase 10–12 class/group workflows. |
| Phase 9 | Panelist and Dean Dashboard UI Shell | Completed | UI shells remain available; later panelist/dean backend workflows remain separate planned phases. |
| Phase 10 | Research Class Management | Completed | Rebuilt facilitator-owned class creation, class cards, class details, and active student roster. Commit: `8be86fb`; tracker completion: `3e45d23`. |
| Phase 11 | Student Join Class Requests | Completed | Rebuilt join-code requests, facilitator approval/rejection, one-active-class enforcement, pending request limits, rejection cooldown/history, and failed-code throttling. Commit: `faa805d`; tracker completion: `c7a1c02`. |
| Phase 12 | Research Groups and Adviser Assignment | Completed | Rebuilt facilitator group creation, active student assignment (maximum 4), move logic, unassigned-student roster, disbanding, adviser request/accept/decline/cancel workflow, adviser removal/history, and scoped student/adviser visibility. Later refinements improve My Classes, member display, Group Leader visibility, and class-detail presentation without changing the completed phase boundary. |
| Phase 13 | Document Upload and Secure Storage | Completed | Research-group ownership, designated Group Leader-only submission, PDF/DOCX validation up to 10 MB, private UUID storage, SHA-256 duplicate protection, CURRENT/VOID version history, upload auditing, and Group Leader management are implemented. Latest UI refinement keeps submission in the Research Proposal area rather than exposing an upload shortcut in the Repository. |
| Phase 14 | Research Repository | Completed | Implemented controlled submission stages, group/stage CURRENT/VOID versioning, student/adviser/facilitator/admin repository scopes, disbanded-member history, validated search/filters/sorting, 10-record pagination, version history, protected PDF view, DOCX metadata/download behavior, secure downloads, and access auditing. Verified by the Phase 10–14 regression suite: 115 tests executed, 94 passed, 21 skipped, 496 assertions, zero failures; Pint and the production frontend build passed. Commit: `173e8f2`. |
| Phase 15 | Adviser Document Review | Completed | Implemented group-owned adviser review queue, severity-graded finding comments (`comment`, `revision`, `critical`), page-number annotations, finding resolution, acceptance blocking (unresolved revision/critical findings block `accepted` decision), controlled decision correction history (`supersedes_review_id`, `correction_reason`), audit trail, and student feedback visibility. Verified by the Phase 15 test suite: 11 tests executed, 53 assertions, zero failures; Pint and production Vite build passed. |
| Phase 16 | Consultation Records | Planned | Rebuild student booking and adviser/facilitator consultation management. |
| Phase 17 | Revision Tracker | Planned | Rebuild revision requests, revision status transitions, and revision document uploads. |
| Phase 18 | Research Progress Milestones | Planned | Rebuild milestone configuration, progress updates, and dashboard progress cards. Research monitoring milestones are separate from Phase 14 document-submission stages. |
| Phase 19 | Official Research Forms | Planned | Rebuild form data saving, per-role form access, print/export, and approval routing. |
| Phase 20 | Digital Signature Verification | Planned | Rebuild user signature enrollment, approval routing, signature metadata, and QR verification. |
| Phase 21 | Defense Scheduling | Planned | Rebuild defense requests, schedules, rooms, and calendar integration. |
| Phase 22 | Evaluation Records | Planned | Rebuild panelist evaluation forms, summaries, and student-visible results. |
| Phase 23 | Notifications | Planned | Rebuild system notifications for requests, reviews, revisions, consultations, and approvals. Sidebar presence does not mean the notification backend is complete. |
| Phase 24 | Audit Logs | Planned | Rebuild feature-level audit logs beyond the admin/auth and feature-specific audit foundations already present. |
| Phase 25 | Reports and Analytics | Planned | Rebuild dashboard metrics, exports, and reporting queries. |
| Phase 26 | Security Review and Hardening | Planned | Validate authorization, rate limits, file handling, SQL injection protection, XSS handling, error safety, and cross-feature security. |
| Phase 27 | Testing and Final Documentation | Planned | Complete integration/system tests, user guide, technical documentation, and final capstone evidence. |

## Latest Verified Development Review

### `ab529a5` — Research Class Group / Group Leader UX refinement

**Change type:** Completed Phase 12/13 workflow and presentation refinement.
**Verified impact:** Student document submission redirects to the Research Proposal tab; the Student Repository no longer presents a document-upload shortcut; student class details load the Group Leader; adviser assigned-group cards identify the Student Leader; facilitator leader controls distinguish assign/change behavior; regression tests were adjusted around these behaviors.
**Phase impact:** Phase 12 and Phase 13 remain Completed. This commit does not prove Phase 14 is complete.

### `a8320f9` — Phase 14 ownership/access foundation

**Change type:** Authorization/query foundation correction.
**Verified impact:** Group-linked documents use Research Class Group ownership for policy/access checks; `documents.user_id` remains uploader/accountability metadata. Adviser scope follows the owning group. Student document counts/search/repository data use the active group. Narrow null-group compatibility remains isolated from modern group-owned documents.
**Phase impact:** Phase 14 is In Progress.

### `173e8f2` — Phase 14 Research Repository

**Change type:** Complete Phase 14 implementation

**Verified impact:** Group-owned, stage-aware repository records now support scoped browsing, validated search/filter/sort, pagination, CURRENT/VOID history, protected view/download, disbanded-member historical access, and successful-access auditing. `user_id` remains immutable uploader metadata while the Research Class Group remains the owner.

**Boundary:** Adviser upload remains disabled under the Group Leader-only rule. Comments, review decisions, and revision actions remain disabled for Phase 15. Repository uploads do not modify Phase 18 progress milestones.

**Verification:** The Phase 10–14 regression run completed with 115 tests executed, 94 passed, 21 intentionally skipped, 496 assertions, and zero failures. `vendor/bin/pint --test`, Blade compilation, and `npm run build` passed. The reversible Phase 14 migration was applied successfully to the configured local MySQL database.

**Phase impact:** Phases 13 and 14 are Completed. Phase 15 has not started.

### `db7775b0` — Phase 13 secure group document submission

**Change type:** Phase 13 feature implementation.
**Verified impact:** Introduced Research Group document ownership, Group Leader-only uploads, secure PDF/DOCX storage, SHA-256 duplicate rejection, CURRENT/VOID versioning, upload audit records, and Group Leader management.
**Recorded verification:** Phase 13 documentation records 17 focused document-submission tests with 101 assertions, plus successful Pint formatting and frontend build.
**Phase impact:** Phase 13 Completed.

## Documentation Workflow

For each backend feature:

1. Read the current active repository and identify the actual implementation baseline.
2. Create or update the relevant feature documentation.
3. Explain the complete user and backend flow, including routes, middleware/permissions, validation, controllers, services/actions, models, database changes, and responses.
4. Identify every important file added or modified and explain why it changed.
5. Document database tables, relationships, migrations, seeders, status transitions, and business rules where applicable.
6. Document authentication, authorization/RBAC, ownership checks, validation, rate limiting, file security, audit behavior, and other security controls where applicable.
7. Add or update tests for validation, authorization, success paths, failure paths, IDOR boundaries, concurrency where applicable, and important edge cases.
8. Record only test/build evidence that was actually executed and available; do not invent counts or results.
9. Update this tracker based on verified implementation evidence.
10. Push focused commits only to `KMForge/NDMU-RMAS`.

Documentation must be detailed enough that the capstone manuscript/documentation writer can understand the feature without reverse-engineering the Laravel source code. Implemented behavior must be clearly separated from planned or unverified behavior.

## Protected Repositories

Do not modify these repositories unless explicitly requested:

- `KMForge/NDMU-RMAS-Legacy`
- `KMForge/NDMU-RMAS-Rebuild`
