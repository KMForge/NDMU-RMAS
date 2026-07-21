# Modules

| Module | Responsibility |
|---|---|
| Authentication | Registration, verification, approval, sessions |
| Dashboard | Shared and role-scoped summaries |
| ResearchManagement | Research aggregate and lifecycle orchestration |
| ResearchProposal | Proposal submission and review |
| ResearchProgress | Milestones and progress evidence |
| ConsultationRecords | Adviser consultation records |
| RevisionTracker | Revision requests and resolution |
| DefenseScheduling | Rooms, schedules, conflicts, and panels |
| EvaluationSystem | Rubrics, scores, and access restrictions |
| ResearchRepository | Approved archive discovery and protected access |
| Notifications | In-app, mail, and optional realtime delivery |
| UserManagement | Accounts, status, roles, and permissions |
| FacultyManagement / StudentManagement | Role-specific profile administration |
| AdviserAssignment / PanelAssignment | Scoped research assignments |
| ResearchStatistics / ReportsAnalytics | Aggregation, charts, and exports |
| AuditLogs | Security-relevant activity history |
| SystemSettings | Restricted configuration management |

Each module starts with a README. Add `Actions`, `Contracts`, `Data`, `Livewire`, `Policies`, `Queries`, or `Services` only when real implementation requires it. Models stay global and HTTP controllers stay under `app/Http/Controllers`.
