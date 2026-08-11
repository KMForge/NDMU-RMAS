# Phase 18 — Research Progress Milestones

## Purpose and boundary

Phase 18 records where a `ResearchClassGroup` is in NDMU's official research process. It is group-owned: every current member sees the same state and verified former members retain historical read-only access.

This workflow remains separate from document stages, adviser reviews, consultations, and revision cycles. Those records may be linked as evidence, but they never start, complete, or reverse a milestone automatically. Phase 18 sends no notifications and does not implement forms, signatures, defense scheduling, or evaluation logic.

## Authoritative milestones

Definitions are centralized in `config/research-progress.php`, persisted in `milestone_definitions`, synchronized via `SyncResearchMilestoneDefinitions`, and initialized idempotently for active groups:

1. Research Title Presentation (`research-title-presentation`)
2. Formulation of Research Proposal (`formulation-research-proposal`)
3. Research Proposal Defense (`research-proposal-defense`)
4. Revision of Research Proposal Paper (`revision-research-proposal`)
5. Validation of Survey Instrument (`validation-survey-instrument`)
6. Submission of the Complete Research Proposal Paper & Others (`submission-complete-research-proposal`)
7. Data Gathering (`data-gathering`)
8. Data Processing (`data-processing`)
9. Report Writing (`report-writing`)
10. Research Final/Oral Defense (`research-final-oral-defense`)
11. Revision of the Whole Research Paper (`revision-whole-research-paper`)
12. Language and Technical Editing (`language-technical-editing`)
13. Submission of the Final Copy of the Research Paper (`submission-final-research-paper`)

### Architectural Boundaries & Layering
- **Official Research Writing Phases (Phase 18)**: Top-level process progression (the 13 phases above).
- **Document Stages**: File upload categories (`DocumentStage::ProposalDefense`, `DocumentStage::FinalDefense`, etc.).
- **Official Forms (Phase 19)**: Form workflows (`RES-Form-026`, `RES-Form-034`, `RES-Form-045`, `RES-Form-047`, etc.).
- **Digital Signatures (Phase 20)**: Signature sign-offs attached to versioned official form submissions.

The stable code, display name, sequence, active flag, and weight belong to the definition. A unique constraint permits only one group record per definition. Database weights can be adjusted centrally without changing controllers or Blade files; active weights must be positive.

New groups created through `CreateResearchClassGroup` receive all active milestone rows inside the group-creation transaction. The idempotent initializer also runs on progress reads and through `ResearchProgressSeeder`, safely backfilling eligible existing groups without duplicating rows.

## Ownership and access

| Actor | Access |
| --- | --- |
| Current group member | Read own group progress |
| Verified former member | Read historical group progress |
| Current assigned adviser | Read assigned group progress |
| Historical adviser | Read disbanded-group history when adviser history proves assignment |
| Owning research facilitator | Read and mutate active groups in classes they currently own |
| Other facilitator | No access through URL ID changes |
| Administrator | System-wide read only when `progress.view-all` is explicitly assigned |

The Group Leader has no additional Phase 18 authority. A multi-role faculty user's effective authority depends on both permission and the group/class relationship. Base `faculty` or `admin` identity never grants academic mutation.

## State and percentage model

Allowed states are `Pending`, `In Progress`, `Completed`, and `Not Applicable` through `ResearchMilestoneStatus`.

Official percentage is always derived:

```text
sum(completed applicable active weights)
------------------------------------------------ × 100
sum(all applicable active milestone weights)
```

Pending and In Progress contribute zero. Not Applicable is excluded from the denominator and requires a reason. The system accepts no arbitrary percentage input. `Overdue` is presentation state derived from an optional past due date while status is Pending/In Progress.

The current milestone is the first active/applicable milestone not completed. If all applicable milestones are completed, progress is 100%.

## Transitions, ordering, and corrections

Normal transitions are `Pending -> In Progress -> Completed`. Earlier applicable milestones must be completed or marked Not Applicable before a later milestone starts/completes.

An out-of-order action requires all of:

- `progress.override-order`;
- owning-facilitator context;
- explicit override/direct-completion input;
- a bounded plain-text reason.

Completed status can be corrected to Pending, In Progress, or Not Applicable with a reason. Not Applicable can be re-enabled to Pending with a reason. Corrections preserve the old completion in event history even when current completion columns are cleared.

Status changes, due-date changes, controlled overrides, corrections, and evidence links execute in transactions with row/group locking and authorization rechecks. This prevents stale requests and concurrent sequence bypass.

## Due dates, evidence, and history

The owning facilitator may set, change, or clear a milestone due date. Old/new values, actor, time, reason, and IP are recorded.

Supported Phase 18 evidence types are:

- document;
- document review;
- revision request;
- consultation record.

Evidence is verified against the same `research_class_group_id`; arbitrary or cross-group IDs are rejected. API responses expose only safe summaries and never document storage paths. Evidence linking is idempotent and audited.

`research_group_milestone_events` preserves actor, old/new status, reason, snapshots, override flag, IP, and occurrence time. Disbanded groups retain existing milestones/history and reject normal mutation.

## Database

| Table | Purpose |
| --- | --- |
| `milestone_definitions` | Versionable central definitions and weights |
| `research_group_milestones` | Current state per group and definition |
| `research_group_milestone_events` | Immutable transition/change history |
| `milestone_evidences` | Same-group references to existing records |

Migration `2026_08_11_000002_create_group_owned_research_progress_tables.php` removes the explicitly disposable legacy/test progress tables and installs the new portable schema. It does not modify users. Migration `2026_08_11_000003_enable_rls_on_research_progress_tables.php` enables PostgreSQL RLS for the new tables; it is a no-op on MySQL/SQLite, preserving local development portability.

The configured Supabase PostgreSQL database is authoritative for this phase. Local MySQL/WAMP remains supported through database-neutral Laravel migrations and query-builder/Eloquent code.

## RBAC and policy enforcement

Permissions:

- `progress.view-own`
- `progress.view-assigned`
- `progress.view-owned-classes`
- `progress.manage-owned-classes`
- `progress.override-order`
- `progress.view-all`

Routes first enforce authentication, verified email, active account, workspace context, permission middleware, and `progress-actions` rate limiting. `ResearchGroupMilestonePolicy` and `ResearchProgressAccess` then enforce membership, adviser assignment/history, owning facilitator, group state, and explicit admin view permission. Domain actions repeat mutation authorization after acquiring database locks.

## Routes

Read-only:

- `GET /student/groups/{group}/progress`
- `GET /adviser/groups/{group}/progress`
- `GET /facilitator/groups/{group}/progress`

Owning facilitator mutations:

- `PATCH /facilitator/progress/{milestone}/start`
- `PATCH /facilitator/progress/{milestone}/complete`
- `PATCH /facilitator/progress/{milestone}/correct`
- `PATCH /facilitator/progress/{milestone}/not-applicable`
- `PATCH /facilitator/progress/{milestone}/due-date`
- `POST /facilitator/progress/{milestone}/evidence`

## UI and query behavior

- Student Progress is a read-only 12-step group timeline with derived percentage, dates, N/A state, remarks, evidence count, and audit history.
- Adviser Research Monitoring is read-only for currently assigned groups.
- Facilitator Research Monitoring is a 10-group paginated owned-class view with search, active/disbanded filter, group/adviser/member context, percentage, timeline, due-date, transition, correction, N/A, and evidence controls.
- Student/adviser/admin summaries now derive progress from group milestones rather than the retired free-form percentage table.
- Queries eager-load definitions, events/actors, evidence, class, adviser, and members to prevent repeated relationship queries.

## Validation and security

- IDs use route-model binding plus contextual policy checks to prevent IDOR.
- Reasons, remarks, and evidence summaries are length-limited and stored as stripped plain text; Blade escapes them.
- Statuses and evidence types are allowlisted.
- Percentage, actor, ownership, ordering, timestamps, and group relationship are server-derived.
- Mutations are throttled to 30 per minute per authenticated user/IP key.
- PostgreSQL RLS blocks direct anonymous Supabase API exposure; Laravel still applies application-layer RBAC and record policies.

## Verification coverage

The Phase 18 feature suite verifies idempotent 12-row initialization, weighted calculation, no manual percentage control, sequential enforcement, override reason/audit, N/A denominator/re-enable, completion correction, due-date audit/overdue derivation, same-group member equality, former/disbanded history, adviser read-only access, owning-facilitator scope, multi-role context, evidence IDOR, no accepted-document auto-completion, and no Phase 18 notification dispatch.

Cross-phase regression tests verify completed consultations and resolved revisions do not mutate Phase 18 state.

## Verified implementation result

- Database target: configured Supabase PostgreSQL connection (`pgsql`).
- Local portability: the schema and application queries remain compatible with MySQL/WAMP and SQLite; PostgreSQL-only RLS statements are guarded by the connection driver.
- Migration state: both Phase 18 migrations are applied.
- Live Phase 18 data: 12 definitions and 24 initialized group milestones; 27 existing user accounts were preserved.
- RLS: enabled on all four Phase 18 tables.
- Focused Phase 18 and cross-phase regression suite: 60 tests passed with 236 assertions.
- Full repository suite: 264 tests executed; 228 passed, 23 skipped, 12 failed, and 1 errored (1,154 assertions). No Phase 18 test failed. Remaining failures are stale assertions for intentionally disabled Phase 19 official forms, Phase 20 signatures, the retired `faculty-member` role, and older dashboard UI expectations.
- `vendor/bin/pint --test`: passed.
- Blade view compilation: passed.
- `npm run build`: passed.

The broader test suite still contains known expectations for later disabled phases and older dashboard UI behavior. Those failures are outside the locked Phase 18 boundary and were not hidden by reactivating Phase 19 or Phase 20 functionality.

## Main implementation files

- `config/research-progress.php`
- `app/Enums/ResearchMilestoneStatus.php`
- `app/Models/MilestoneDefinition.php`
- `app/Models/ResearchGroupMilestone.php`
- `app/Models/ResearchGroupMilestoneEvent.php`
- `app/Models/MilestoneEvidence.php`
- `app/Modules/ResearchProgress/Actions/*`
- `app/Modules/ResearchProgress/Queries/*`
- `app/Modules/ResearchProgress/Support/ResearchProgressAccess.php`
- `app/Policies/ResearchGroupMilestonePolicy.php`
- `app/Http/Controllers/{Student,Adviser,Facilitator}/ResearchProgressController.php`
- `resources/views/components/research-progress/facilitator-monitoring.blade.php`
- `tests/Feature/ResearchProgress/ResearchProgressMilestoneTest.php`
