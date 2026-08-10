# Modules

NDMU-RMAS is a Laravel modular monolith. Domain behavior is organized under `app/Modules`, while HTTP controllers, models, middleware, policies, and presentation remain in their conventional Laravel locations when appropriate.

The table below distinguishes the intended domain responsibility from the currently verified rebuild state. A module name in the architecture does not by itself mean that its complete workflow is active.

| Module / Domain | Responsibility | Current verified rebuild state |
|---|---|---|
| Authentication | Registration, verification, approval, sessions, account eligibility | Authentication/account access completed; registration foundation remains in progress |
| Dashboard | Shared and role-scoped summaries | Core dashboard/system shell phases completed for current roles |
| UserManagement | Accounts, status, roles, permissions | Completed foundation |
| Classes / Research Class workflow | Facilitator-owned classes, join requests, enrollment, group organization | Phases 10–12 completed |
| Research Group / Adviser Assignment | Groups, max-four membership, leader assignment, adviser request/accept/decline/change history | Phase 12 completed |
| Documents | Secure group-owned research submission, storage, versioning, audits | Phase 13 completed and extended with Phase 14 stage-aware versioning |
| ResearchRepository | Scoped repository discovery, safe metadata, search/filter/sort/pagination, version history, protected view/download, access audit | Phase 14 completed |
| ResearchProposal | Proposal-oriented submission/presentation workflows | Current student document submission UI uses the Research Proposal area; later proposal-review behavior must follow its verified phase boundary |
| Adviser Document Review | Adviser comments, review decisions, revision requests | Phase 15 planned / not started in the verified tracker |
| ConsultationRecords | Adviser consultation records | Phase 16 planned |
| RevisionTracker | Revision requests and resolution | Phase 17 planned |
| ResearchProgress | Milestones and progress evidence | Phase 18 planned; separate from document submission stages |
| Official Research Forms | Research form workflows, approvals, print/export | Phase 19 planned |
| Digital Signatures | Signature enrollment, approval, verification | Phase 20 planned |
| DefenseScheduling | Rooms, schedules, conflicts, and panels | Phase 21 planned |
| EvaluationSystem | Rubrics, scores, and access restrictions | Phase 22 planned |
| Notifications | In-app, mail, and optional realtime delivery | Phase 23 planned as a rebuilt backend workflow; UI presence is not completion evidence |
| AuditLogs | Cross-feature security-relevant activity history | Phase 24 planned; completed earlier phases already contain specific audit mechanisms where documented |
| ResearchStatistics / ReportsAnalytics | Aggregation, charts, exports | Phase 25 planned |
| Security Review | Cross-feature security validation and hardening | Phase 26 planned |
| Testing / Final Documentation | Integration/system testing and final capstone evidence | Phase 27 planned; phase-specific tests/docs already exist for completed modules |

## Completed Document Domain Boundary

The current document workflow is split intentionally:

### Phase 13 — submission/storage
Group Leader
→ validated PDF/DOCX and submission stage
→ private storage
→ SHA-256 / duplicate protection
→ group + stage version transition
→ upload audit.

### Phase 14 — repository/read access
Authorized user
→ scoped repository query or document binding
→ permission + record-level policy/access service
→ private-file existence check
→ safe metadata / PDF inline view / authorized download / version history
→ successful access audit for view/download.

Phase 14 does not perform adviser review decisions. Those remain Phase 15.

## Module Design Rule

Shared workflows should not be copied into separate role implementations. Role-specific dashboards are presentation boundaries; domain actions/queries/services should be shared when they represent the same business rule.

Add `Actions`, `Contracts`, `Data`, `Livewire`, `Policies`, `Queries`, `Rules`, `Support`, or `Services` only when the actual implementation requires them. Models remain global and HTTP controllers remain under `app/Http/Controllers` unless a verified architectural change says otherwise.
