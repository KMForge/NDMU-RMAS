# NDMU-RMAS Development Progress Tracker

Notre Dame of Marbel University Research Management and Assistance System (NDMU-RMAS)

This tracker records the backend rebuild progress after the baseline reset. GitHub is the source of truth for code history, while this document summarizes the project phases for capstone tracking and reporting.

## Current Baseline

| Item | Status |
| --- | --- |
| Active repository | `KMForge/NDMU-RMAS` |
| Backup repositories | `KMForge/NDMU-RMAS-Legacy`, `KMForge/NDMU-RMAS-Rebuild` |
| Rebuild baseline commit | `f701324 chore: disable feature backends for rebuild baseline` |
| Latest reviewed commit | `8be86fb feat: implement Phase 10 research class management, including class creation and detail viewing` |
| Backend strategy | Rebuild feature modules one at a time |
| UI strategy | Keep existing UI while reconnecting backend modules |

> **Baseline note:** Commit `f701324` remains the functional rebuild baseline. Commit `8be86fb` is the first rebuilt feature module after that baseline and reconnects only Phase 10 Research Class Management.

## Phase Tracker

| Phase | Module | Status | Notes |
| --- | --- | --- | --- |
| Phase 1 | Authentication & Account Access | Completed | Login, logout, session handling, account status checks, and dashboard redirects are active. |
| Phase 2 | Registration Foundation | In Progress | Registration UI exists; backend flow should be verified and completed before feature rebuilds. |
| Phase 3 | User Management | Completed | Admin user management is active. |
| Phase 4 | Dynamic RBAC with Spatie | Completed | Roles, permissions, role assignment, and permission-based access are active. |
| Phase 5 | Admin Dashboard & System Settings | Completed | Admin dashboard shell, user metrics, and system settings are active. |
| Phase 6 | Student Dashboard UI Shell | Completed | UI remains available; feature data sources are disabled for rebuild. |
| Phase 7 | Adviser Dashboard UI Shell | Completed | UI remains available; feature data sources are disabled for rebuild. |
| Phase 8 | Facilitator Dashboard UI Shell | Completed | UI remains available; class/group/join request backend is disabled for rebuild. |
| Phase 9 | Panelist and Dean Dashboard UI Shell | Completed | UI remains available; feature workflows are not rebuilt yet. |
| Phase 10 | Research Class Management | Completed | Rebuilt facilitator-owned class creation, class cards, class details, and active student roster. Groups, adviser assignment, and join request approval remain reserved for later phases. Commit: `8be86fb`. |
| Phase 11 | Student Join Class Requests | Planned | Rebuild student join requests and facilitator approval/rejection flow. |
| Phase 12 | Research Groups and Adviser Assignment | Planned | Rebuild group creation, student assignment, and adviser assignment. |
| Phase 13 | Document Upload and Secure Storage | Planned | Rebuild PDF/DOCX validation, private storage, audit attempts, and metadata saving. |
| Phase 14 | Research Repository | Planned | Rebuild document listing, search, status filters, secure view, and download. |
| Phase 15 | Adviser Document Review | Planned | Rebuild review queue, comments, approve/request revision/reject actions, and audit trail. |
| Phase 16 | Consultation Records | Planned | Rebuild student booking and adviser/facilitator consultation management. |
| Phase 17 | Revision Tracker | Planned | Rebuild revision requests, revision status transitions, and revision document uploads. |
| Phase 18 | Research Progress Milestones | Planned | Rebuild milestone configuration, progress updates, and dashboard progress cards. |
| Phase 19 | Official Research Forms | Planned | Rebuild form data saving, per-role form access, print/export, and approval routing. |
| Phase 20 | Digital Signature Verification | Planned | Rebuild user signature enrollment, approval routing, signature metadata, and QR verification. |
| Phase 21 | Defense Scheduling | Planned | Rebuild defense requests, schedules, rooms, and calendar integration. |
| Phase 22 | Evaluation Records | Planned | Rebuild panelist evaluation forms, summaries, and student-visible results. |
| Phase 23 | Notifications | Planned | Rebuild system notifications for requests, reviews, revisions, consultations, and approvals. |
| Phase 24 | Audit Logs | Planned | Rebuild feature-level audit logs beyond the admin/auth baseline. |
| Phase 25 | Reports and Analytics | Planned | Rebuild dashboard metrics, exports, and reporting queries. |
| Phase 26 | Security Review and Hardening | Planned | Validate authorization, rate limits, file handling, SQL injection protection, XSS handling, and error safety. |
| Phase 27 | Testing and Final Documentation | Planned | Complete feature tests, integration tests, user guide, technical documentation, and capstone evidence. |

## Latest Commit Review

### `45ff3f0` — `docs: add project progress tracker`

**Change type:** Documentation only  
**Files changed:** `docs/PROJECT_PROGRESS.md`  
**Backend impact:** None  
**Database impact:** None  
**Security/RBAC impact:** None  
**Testing impact:** No application tests are required for the documentation-only change.  
**Phase impact:** No phase status changes. The commit establishes the 27-phase tracker used to document the rebuild.

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
