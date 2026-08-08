# NDMU-RMAS Development Progress Tracker

Notre Dame of Marbel University Research Management and Assistance System (NDMU-RMAS)

This tracker records the backend rebuild progress after the baseline reset. GitHub is the source of truth for code history, while this document summarizes the project phases for capstone tracking and reporting.

## Current Baseline

| Item | Status |
| --- | --- |
| Active repository | `KMForge/NDMU-RMAS` |
| Backup repositories | `KMForge/NDMU-RMAS-Legacy`, `KMForge/NDMU-RMAS-Rebuild` |
| Current baseline commit | `f701324 chore: disable feature backends for rebuild baseline` |
| Backend strategy | Rebuild feature modules one at a time |
| UI strategy | Keep existing UI while reconnecting backend modules |

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
| Phase 10 | Research Class Management | Planned | Rebuild class creation, class cards, student list, and facilitator-owned class details. |
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

## Documentation Workflow

For each backend feature:

1. Create or update the relevant feature documentation.
2. Implement the backend in a focused commit.
3. Add or update tests for validation, authorization, and important workflows.
4. Update this progress tracker.
5. Push the commit to `KMForge/NDMU-RMAS`.

## Protected Repositories

Do not modify these repositories unless explicitly requested:

- `KMForge/NDMU-RMAS-Legacy`
- `KMForge/NDMU-RMAS-Rebuild`
