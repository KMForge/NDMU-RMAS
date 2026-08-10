# NDMU-RMAS Development Progress Tracker

Notre Dame of Marbel University Research Management and Assistance System (NDMU-RMAS)

This tracker records the backend rebuild progress after the baseline reset. GitHub is the source of truth for code history, while this document summarizes the project phases for capstone tracking and reporting.

## Current Baseline

| Item | Status |
| --- | --- |
| Active repository | `KMForge/NDMU-RMAS` |
| Backup repositories | `KMForge/NDMU-RMAS-Legacy`, `KMForge/NDMU-RMAS-Rebuild` |
| Rebuild baseline commit | `f701324 chore: disable feature backends for rebuild baseline` |
| Latest reviewed implementation/UI commit | `ede6384 style: standardize global dashboard sidebars and feature hero banners` |
| Latest completed feature phase | `Phase 13 — Document Upload and Secure Storage` |
| Backend strategy | Rebuild feature modules one at a time |
| UI strategy | Keep existing UI while reconnecting backend modules |

> **Baseline note:** Commit `f701324` remains the functional rebuild baseline. Feature modules are being reconnected one phase at a time. Phases 10, 11, 12, and 13 are now complete. Commit `dccec86` refined the completed Phase 12 Adviser My Classes workflow, `ede6384` standardized dashboard sidebar/feature-banner presentation, and Phase 13 implemented group-owned document submission with Group Leader authorization and CURRENT/VOID versioning.

## Phase Tracker

| Phase | Module | Status | Notes |
| --- | --- | --- | --- |
| Phase 1 | Authentication & Account Access | Completed | Login, logout, session handling, account status checks, and dashboard redirects are active. |
| Phase 2 | Registration Foundation | In Progress | Registration UI exists; backend flow should be verified and completed before feature rebuilds. |
| Phase 3 | User Management | Completed | Admin user management is active. |
| Phase 4 | Dynamic RBAC with Spatie | Completed | Roles, permissions, role assignment, and permission-based access are active. |
| Phase 5 | Admin Dashboard & System Settings | Completed | Admin dashboard shell, user metrics, and system settings are active. |
| Phase 6 | Student Dashboard UI Shell | Completed | UI shell is available. Latest UI refinement standardized dashboard sidebar grouping and feature banners. |
| Phase 7 | Adviser Dashboard UI Shell | Completed | UI shell is available. My Classes now integrates the verified Phase 12 adviser-request/assigned-group workflow. |
| Phase 8 | Facilitator Dashboard UI Shell | Completed | UI shell is available and supports the independently rebuilt Phase 10–12 class/group workflows. |
| Phase 9 | Panelist and Dean Dashboard UI Shell | Completed | UI shells remain available; later panelist/dean feature workflows are still tracked separately. |
| Phase 10 | Research Class Management | Completed | Rebuilt facilitator-owned class creation, class cards, class details, and active student roster. Commit: `8be86fb`; tracker completion: `3e45d23`. |
| Phase 11 | Student Join Class Requests | Completed | Rebuilt join-code requests, facilitator approval/rejection, one-active-class enforcement, pending request limits, rejection cooldown/history, and failed-code throttling. Commit: `faa805d`; tracker completion: `c7a1c02`. |
| Phase 12 | Research Groups and Adviser Assignment | Completed | Rebuilt facilitator group creation, active student assignment (max 4), student move logic, unassigned-student roster, group disbanding, adviser invitation workflow (pending/accept/decline/cancel), adviser removal/history, and scoped visibility. Adviser My Classes was refined with pending-request badge, Accept/Decline confirmations, assigned groups, and member names. Implementation: `c83399c`; refinement: `dccec86`. |
| Phase 13 | Document Upload and Secure Storage | Completed | Rebuilt research group document ownership, Group Leader upload authorization, PDF/DOCX magic-byte validation, private UUID storage, SHA-256 duplicate rejection, CURRENT/VOID document versioning, upload auditing, facilitator leader assignment UI, and student submission UI with version history. |
| Phase 14 | Research Repository | Planned | Rebuild document listing, search, status filters, secure view, and download. |
| Phase 15 | Adviser Document Review | Planned | Rebuild review queue, comments, approve/request revision/reject actions, and audit trail. |
| Phase 16 | Consultation Records | Planned | Rebuild student booking and adviser/facilitator consultation management. |
| Phase 17 | Revision Tracker | Planned | Rebuild revision requests, revision status transitions, and revision document uploads. |
| Phase 18 | Research Progress Milestones | Planned | Rebuild milestone configuration, progress updates, and dashboard progress cards. |
| Phase 19 | Official Research Forms | Planned | Rebuild form data saving, per-role form access, print/export, and approval routing. |
| Phase 20 | Digital Signature Verification | Planned | Rebuild user signature enrollment, approval routing, signature metadata, and QR verification. |
| Phase 21 | Defense Scheduling | Planned | Rebuild defense requests, schedules, rooms, and calendar integration. |
| Phase 22 | Evaluation Records | Planned | Rebuild panelist evaluation forms, summaries, and student-visible results. |
| Phase 23 | Notifications | Planned | Rebuild system notifications for requests, reviews, revisions, consultations, and approvals. Sidebar presence does not mean the notification backend is complete. |
| Phase 24 | Audit Logs | Planned | Rebuild feature-level audit logs beyond the admin/auth baseline. |
| Phase 25 | Reports and Analytics | Planned | Rebuild dashboard metrics, exports, and reporting queries. |
| Phase 26 | Security Review and Hardening | Planned | Validate authorization, rate limits, file handling, SQL injection protection, XSS handling, and error safety. |
| Phase 27 | Testing and Final Documentation | Planned | Complete feature tests, integration tests, user guide, technical documentation, and capstone evidence. |

## Latest Verified Development Review

### `ede6384` — `style: standardize global dashboard sidebars and feature hero banners`

**Change type:** UI/presentation refinement  
**Verified impact:** Standardizes dashboard sidebar grouping, separates bottom Notifications/Settings/Logout actions, separates Official Forms/Research Forms navigation where applicable, and introduces consistent green feature hero banners for applicable feature views.  
**Backend phase impact:** No new backend phase completed.  
**Important boundary:** Visible Notifications, Official Forms, and other later-phase navigation do not prove those later backend workflows are implemented.

### `dccec86` — `feat: refine adviser my classes workflow`

**Change type:** Phase 12 functional/UI refinement  
**Verified impact:** Adviser My Classes now surfaces pending adviser requests, pending count badge, Accept/Decline confirmation flow, assigned research groups, and accepted-group member names. Adviser response redirects return to the classes tab.  
**Recorded repository verification:** Phase 12 documentation records 32 Phase 10–12 class tests passed with 0 failures, including 17 focused Phase 12/group-adviser workspace tests; `vendor/bin/pint --test` passed and `npm run build` completed successfully.  
**Phase impact:** Phase 12 remains Completed.

## Documentation Workflow

For each backend feature:

1. Create or update the relevant feature documentation.
2. Explain the complete user and backend flow, including routes, middleware/permissions, validation, controllers, services/actions, models, database changes, and responses.
3. Identify every important file added or modified and explain why it changed.
4. Document database tables, relationships, migrations, seeders, status transitions, and business rules where applicable.
5. Document authentication, authorization/RBAC, ownership checks, validation, rate limiting, file security, and other security controls where applicable.
6. Add or update tests for validation, authorization, success paths, failure paths, and important edge cases.
7. Record the evidence needed for capstone documentation, such as test output, screenshots, Postman results, UI evidence, and database evidence.
8. Update this progress tracker with the resulting phase status and remaining work.
9. Push the focused commit to `KMForge/NDMU-RMAS`.

Documentation should be detailed enough that the capstone manuscript/documentation writer can understand the feature without first reverse-engineering the Laravel source code. Clearly distinguish implemented behavior from planned behavior.

## Protected Repositories

Do not modify these repositories unless explicitly requested:

- `KMForge/NDMU-RMAS-Legacy`
- `KMForge/NDMU-RMAS-Rebuild`
