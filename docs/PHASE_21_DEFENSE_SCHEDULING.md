# Phase 21 — Defense Scheduling

## Status
- **Phase Status**: Completed
- **Phase Completion**: 100% (Phase-21 Owned Implementation Scope)
- **Dependency correction baseline**: `8b15011507c76d76c221e8be36e9a204fbd67a03`

Phase 21 is classified as Completed because the two former Phase 22 technical dependencies—evaluation processing and defense completion—are now implemented and verified. Remaining institutional policy questions are non-blocking external decisions and are not described as Laravel defects.

## Scope
Phase 21 establishes authoritative defense scheduling capabilities for NDMU-RMAS:
1. Room catalog management (`defense_rooms`) with Admin control, uppercase code normalization, and location tracking.
2. Defense aggregate entity (`defenses`) bound to research class groups with status tracking that includes `scheduled`, `cancelled`, and Phase 22 `completed`.
3. Historical and active schedule occurrence tracking (`defense_schedules`) with superseded audit trail (`status = current | superseded | cancelled`, `supersedes_schedule_id`).
4. Panel member assignment management (`defense_panel_assignments`) with candidate account eligibility checks (Faculty, Active, approved, verified, `evaluations.create`) and overlap conflict prevention.
5. Half-open interval time overlap conflict detection for room, research group, and panelist schedules (`starts_at < proposed_ends_at AND ends_at > proposed_starts_at`).
6. RES-036 form instance contextual creation linked to active `DefenseSchedule` source, with server-derived immutable `source_snapshot` data.
7. Phase 21 supplies the authoritative schedule and panel context consumed by the completed Phase 22 evaluation workflow. Browser-authored RES-036 system scores remain rejected; Phase 22 creates the immutable evaluation-backed version server-side.
8. Real-time dashboard integration rendering live database defense schedules across Panelist, Facilitator, Student, and Adviser interfaces.

## Architecture Overview
Defense scheduling is structured as a modular domain in `app/Modules/DefenseScheduling/`:
- **Models**: `DefenseRoom`, `Defense`, `DefenseSchedule`, `DefensePanelAssignment`.
- **Actions**:
  - `ScheduleDefense`: Atomically locks group, room, and panelist user records for update; validates time bounds, panel eligibility, and half-open time overlap conflicts; creates Defense, DefenseSchedule, and DefensePanelAssignment records; logs system audit trail.
  - `RescheduleDefense`: Transactionally marks previous active schedule `S1` as `superseded` (`status = superseded`), creates new schedule `S2` (`status = current`, `supersedes_schedule_id = S1.id`), updates `Defense.current_schedule_id = S2.id` (while `Defense.status` remains `scheduled`), and records audit trail.
  - `CancelDefense`: Marks current schedule as `cancelled`, updates `Defense.status` to `cancelled`, ends active panel assignments (`ended_at = now()`), and logs cancellation reason.
  - `AssignDefensePanel`: Validates panel candidate eligibility (Faculty, Active, approved, verified, `evaluations.create`), checks schedule overlap across other scheduled defenses, ends removed assignments (`ended_at = now()`), creates new active assignments, and logs audit trail.
- **Queries**:
  - `GetDefenseScheduleCalendar`: Executes role-scoped queries (Student, Faculty facilitator/adviser/panelist, Admin) returning structured schedule DTOs with formatted date/time, room metadata, panelist lists, and RES-036 creation URLs.

## Database Design & Entity Model

### Tables & Relationships
1. **`defense_rooms`**:
   - `id` (bigint, PK)
   - `code` (string, unique, uppercase normalized)
   - `name` (string)
   - `location_notes` (text, nullable)
   - `is_active` (boolean, default true)
   - `timestamps`

2. **`defenses`**:
   - `id` (bigint, PK)
   - `research_class_group_id` (bigint, FK to `research_class_groups`)
   - `defense_type` (string, e.g. `proposal_defense`, `final_defense`)
   - `status` (string, `scheduled` | `cancelled`, default `scheduled`)
   - `current_schedule_id` (bigint, FK to `defense_schedules`, nullable)
   - `created_by` (bigint, FK to `users`)
   - `timestamps`
   - *Note*: Domain logic prevents creating a second active Defense for the same group and defense type.

3. **`defense_schedules`**:
   - `id` (bigint, PK)
   - `defense_id` (bigint, FK to `defenses`)
   - `room_id` (bigint, FK to `defense_rooms`)
   - `starts_at` (timestamp with time zone)
   - `ends_at` (timestamp with time zone)
   - `status` (string, `current` | `superseded` | `cancelled`, default `current`)
   - `scheduled_by` (bigint, FK to `users`)
   - `reason` (text, nullable)
   - `supersedes_schedule_id` (bigint, FK to `defense_schedules`, nullable)
   - `timestamps`

4. **`defense_panel_assignments`**:
   - `id` (bigint, PK)
   - `defense_id` (bigint, FK to `defenses`)
   - `user_id` (bigint, FK to `users`)
   - `assigned_by` (bigint, FK to `users`)
   - `assigned_at` (timestamp with time zone)
   - `ended_at` (timestamp with time zone, nullable)
   - `timestamps`

## Authorization & Security Boundaries

### Account Eligibility Rules
Panel candidates must satisfy:
- `user_type === UserType::Faculty`
- `status === AccountStatus::Active`
- `approved_at IS NOT NULL`
- `email_verified_at IS NOT NULL`
- `can('evaluations.create')`

### Action Ownership Rules
- Only the Research Class Facilitator owning the underlying class (`research_class.facilitator_id === actor.id`) with `defenses.manage` permission may schedule, reschedule, cancel a defense, or assign panel members.
- Administrative accounts (`UserType::Admin`) have read visibility but cannot schedule or mutate defense schedules directly ("Fail-Closed" administrative boundary).

### Phase 21 / Phase 22 integration
Form RES-036 uses the current `DefenseSchedule` and frozen panel assignment as authoritative context. `OpenDefenseEvaluationRound` freezes the exact schedule, three eligible active panelists, and student roster. `SubmitDefenseEvaluation::createRes036Instance()` writes the submitted panelist scores into an immutable server-derived form version while retaining the schedule source and exact `defense_evaluation_id`. Browser-submitted RES-036 system scores remain rejected by the generic official-form payload validator and policy boundary.

## Transactional Source Linkage & Revalidation
In `CreateOfficialFormInstance::validateSourceLinkage()`:
- DB pessimistic row locking (`lockForUpdate()`) locks `DefenseSchedule` and parent `Defense`.
- Revalidates that schedule status is `current`, defense status is `scheduled`, `defense.current_schedule_id === schedule.id`, group matches request context, initiator is an active panel member (`DefensePanelAssignment`), and initiator meets full account eligibility.

## Panelist Dashboard Integration
- Live database defense schedules are rendered dynamically on the Panelist Dashboard (`/panelist/dashboard?tab=schedule`).
- Panel members see their assigned defenses, date/time, venue details, and co-panelists.
- Eligible panel members see an **"Open RES-036 Form"** button triggering contextual form instance creation directly from the schedule source.
- Evaluation tabs display a clear notice banner stating: *"Evaluation Record scoring forms and verdicts will be active in Phase 22."*

## Dependency table

| Dependency | Type | Evidence | Current status | Required owner/action |
| --- | --- | --- | --- | --- |
| Evaluation scoring and summary workflow | Cross-phase | `OpenDefenseEvaluationRound`, `SubmitDefenseEvaluation`, `DefenseEvaluationTest`; commits `7660e15`, `d098c94` | Resolved | None |
| Defense completion transition | Cross-phase | `CompleteDefenseAfterEvaluation` requires a released evaluation round; Phase 22 lifecycle tests | Resolved | None |
| RES-036 schedule/panel source | Cross-phase | `ScheduleDefense`, `DefensePanelAssignment`, `SubmitDefenseEvaluation::createRes036Instance`, `DefenseFormIntegrationTest` | Resolved | None |
| Formal defense application/request | Institutional policy | No approved request aggregate, actor, or lifecycle is present | Open; non-blocking external decision | NDMU Research Office must define requester, approver, prerequisites, and rejection/cancellation behavior |
| Re-defense/second attempt | Institutional policy | `ScheduleDefense` fails closed when a defense aggregate already exists for the group/type | Open; non-blocking external decision | Define eligibility, attempt numbering, relation to prior verdict, and retained history |
| Chairperson and panel composition | Institutional policy | Current project rule supports one chairperson plus two members; adviser cannot chair the same group but may be a member; Phase 22 requires exactly three active assignments | Implemented project design rule; institutional confirmation pending | Confirm chair authority, minimum/maximum size, substitutes, and whether the implemented rule is official |

The code does not assume a first panelist is the chairperson. `panel_position` stores explicit `chairperson`, `member_1`, and `member_2` positions when the configured three-person roster is used.

## Final Post-Reopen Verification Evidence

Re-verification executed against HEAD `b3d26fe` on August 15, 2026.

### Reopened Fixes Confirmed in Source
- CancelDefense ends active panel assignments (`ended_at = now()` where `ended_at IS NULL`), preserves historical rows, cancels current schedule, clears `Defense.current_schedule_id`, sets `Defense.status = cancelled`, reauthorizes inside transaction.
- AssignDefensePanel reauthorizes inside transaction, validates facilitator-owned class, blocks cancelled Defense panel mutation.
- ScheduleDefense reauthorizes locked/fresh group context inside transaction.
- RescheduleDefense reauthorizes locked/fresh group/Defense context inside transaction.
- GetDefenseScheduleCalendar Student scope uses `defense.group.members` with `student_id` (no `group.enrollments`).
- Student DashboardController injects `GetDefenseScheduleCalendar` and passes `$data['defenses']`.
- Adviser dashboard Blade consumes `$adviserDefenses` via `@forelse`.
- Facilitator dashboard live Defense list uses `@json($defenseListData)` computed from server-provided `$defenses`.
- Panelist dashboard transforms `$assignedDefenses` into `$formattedDefenses` from database.

### Source Defects Found During Re-Verification
- None.

### Focused Phase 21 Test Suite
- `tests/Feature/DefenseSchedulingTest.php`: 13 tests, PASSED
- `tests/Feature/DefenseSecurityTest.php`: 4 tests, PASSED
- `tests/Feature/DefenseFormIntegrationTest.php`: 8 tests, PASSED
- `tests/Feature/DefenseDashboardIntegrationTest.php`: 2 tests, PASSED
- **Summary**: 27 tests, 27 passed, 0 failed, 0 skipped, 65 assertions.

### Research Progress Regression
- 20 tests, 20 passed, 0 failed, 0 skipped, 94 assertions (PASSED).

### Official Forms Regression
- 78 tests, 78 passed, 0 failed, 0 skipped, 379 assertions (PASSED).

### Signature Regression
- `tests/Feature/Signatures/`: 11 tests, 10 passed, 1 skipped, 0 failed, 55 assertions (PASSED).
- `OfficialFormSignatureTest.php` + `OfficialFormVerificationTest.php`: 20 tests, 20 passed, 0 failed, 0 skipped, 66 assertions (PASSED).

### Dashboard Regression
- `AdminDashboardTest.php`, `AdviserDashboardOverviewTest.php`, `DefenseDashboardIntegrationTest.php`, `StudentDashboardDataTest.php`: 29 tests, 29 passed, 0 failed, 0 skipped, 153 assertions (PASSED).

### Consultation / Source Regression
- `tests/Feature/Consultations/`: 19 tests, 17 passed, 2 skipped, 0 failed, 67 assertions (PASSED).

### Full Application Test Suite
- 367 tests total, 343 passed, 24 skipped, 0 failed, 1,533 assertions (PASSED, exit code 0).

### Quality Gates
- `vendor/bin/pint --test`: **PASS** (0 style violations)
- `npm run build`: **PASS** (Vite v8.1.5 build completed in 1.50s)
- `php artisan view:clear && view:cache`: **PASS** (Blade templates cached successfully)
- `php artisan migrate:status`: **PASS** (All 45 migrations Ran)
- `composer.json`: **PASS** (valid JSON)
- `git diff --check`: **PASS** (0 whitespace errors)

### Static Defense Data Audit
- Facilitator dashboard overview tab contains demo `defenses` array (L171-175) for the overview widget — **not** used by the live Phase 21 Defense Scheduling tab which uses `defenseList` from `@json($defenseListData)`.
- Panelist dashboard uses `$formattedDefenses` from `$assignedDefenses` (database-backed) with a static fallback array only when empty.
- Dean dashboard contains overview demo data — not part of Phase 21 scope.
- No invalid `group.enrollments` relationship exists anywhere in `app/`.

## Current dependency-correction verification

The historical verification report above is retained as implementation history. The current-baseline focused results for this audit are:

| Command | Result |
| --- | --- |
| `php artisan test --filter=Defense` | PASS — 66 tests, 202 assertions, 0 failures |
| `php artisan test --filter=DefenseEvaluation` | PASS — 26 tests, 84 assertions, 0 failures |

No Phase 21 application code or test was changed during this dependency-correction pass.

## Definition of Done
Phase 21 is **Completed** with verified scheduling, conflict protection, historical schedule handling, panel assignment, Phase 22 evaluation integration, and defense completion integration. The remaining institutional policy questions are explicitly non-blocking external decisions and do not conceal a missing Phase 21 technical dependency.
