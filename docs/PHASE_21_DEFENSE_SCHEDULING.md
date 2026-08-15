# Phase 21 — Defense Scheduling

## Status
- **Phase Status**: Completed with External Dependencies
- **Phase Completion**: 100% (Phase-21 Owned Implementation Scope)
- **Verification Date**: August 15, 2026

## Scope
Phase 21 establishes authoritative defense scheduling capabilities for NDMU-RMAS:
1. Room catalog management (`defense_rooms`) with Admin control, uppercase code normalization, and location tracking.
2. Defense aggregate entity (`defenses`) bound to research class groups with active defense status tracking (`scheduled`, `cancelled`).
3. Historical and active schedule occurrence tracking (`defense_schedules`) with superseded audit trail (`status = current | superseded | cancelled`, `supersedes_schedule_id`).
4. Panel member assignment management (`defense_panel_assignments`) with candidate account eligibility checks (Faculty, Active, approved, verified, `evaluations.create`) and overlap conflict prevention.
5. Half-open interval time overlap conflict detection for room, research group, and panelist schedules (`starts_at < proposed_ends_at AND ends_at > proposed_starts_at`).
6. RES-036 form instance contextual creation linked to active `DefenseSchedule` source, with server-derived immutable `source_snapshot` data.
7. Strict Phase 21/Phase 22 security boundary: RES-036 payload schema strictly locked to empty array `[]` during Phase 21, and form mutation/evaluation policy checks explicitly return `false` (evaluation scoring and verdicts deferred to Phase 22 Evaluation Records).
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

### Phase 21 / Phase 22 Boundary Isolation
Form RES-036 (Defense Evaluation Sheet) is bound to `DefenseSchedule` as its authoritative source. During Phase 21:
- `OfficialFormPayloadValidator` schema for `RES-036` is strictly set to `[]` (empty array). Browser-submitted payload fields are rejected.
- `OfficialFormInstancePolicy` intercepts `updateDraft`, `submit`, `endorse`, `certify`, `approve`, `receive`, `validate`, and `evaluate` for RES-036 instances backed by `DefenseSchedule`, immediately returning `false`.
- Evaluation scoring, rating entries, rubrics, and final pass/fail verdicts are explicitly deferred to **Phase 22 (Evaluation Records)**.

## Transactional Source Linkage & Revalidation
In `CreateOfficialFormInstance::validateSourceLinkage()`:
- DB pessimistic row locking (`lockForUpdate()`) locks `DefenseSchedule` and parent `Defense`.
- Revalidates that schedule status is `current`, defense status is `scheduled`, `defense.current_schedule_id === schedule.id`, group matches request context, initiator is an active panel member (`DefensePanelAssignment`), and initiator meets full account eligibility.

## Panelist Dashboard Integration
- Live database defense schedules are rendered dynamically on the Panelist Dashboard (`/panelist/dashboard?tab=schedule`).
- Panel members see their assigned defenses, date/time, venue details, and co-panelists.
- Eligible panel members see an **"Open RES-036 Form"** button triggering contextual form instance creation directly from the schedule source.
- Evaluation tabs display a clear notice banner stating: *"Evaluation Record scoring forms and verdicts will be active in Phase 22."*

## External / Institutional Dependencies
The following institutional workflow rules are deferred to subsequent phases or external policy decisions:
1. **Formal Defense Application / Request Workflow**: Student or adviser formal submission requesting a defense date prior to facilitator scheduling.
2. **Re-defense & Second Attempt Policy**: Formal rules for permitting a second defense aggregate after a previous defense aggregate was cancelled or failed.
3. **Panel Chair Designation & Minimum Panel Size**: Specific role designation for Panel Chair vs Panel Members (currently all assigned faculty share equal panelist access).
4. **Evaluation Scoring & Verdict Workflow**: Panelist evaluation scores, rubrics, ratings, grading summaries, and pass/fail/revision verdicts (owned by **Phase 22: Evaluation Records**).
5. **Defense Completion Status Transition**: Automatic or manual transition of `defenses.status` to `completed` upon post-defense verdict submission (owned by **Phase 22: Evaluation Records**).

## Verification Evidence
Actual execution outputs recorded from the codebase:

- **Focused Phase 21 Test Suite**:
  - `tests/Feature/DefenseSchedulingTest.php` (13 tests, PASSED)
  - `tests/Feature/DefenseSecurityTest.php` (4 tests, PASSED)
  - `tests/Feature/DefenseFormIntegrationTest.php` (8 tests, PASSED)
  - `tests/Feature/DefenseDashboardIntegrationTest.php` (2 tests, PASSED)
  - **Summary**: 27 tests, 27 passed, 0 failures, 65 assertions (Duration: ~57.2s).
- **Research Progress Regression**: 20 tests, 20 passed, 0 failures, 94 assertions (PASSED).
- **Official Forms Regression**: 78 tests, 78 passed, 0 failures, 379 assertions (PASSED).
- **Signature Regression**: 31 tests, 30 passed, 1 skipped, 0 failures, 121 assertions (PASSED).
- **Dashboard Regression**: 8 tests, 8 passed, 0 failures, 49 assertions (PASSED).
- **Full Application Test Suite**: 367 tests total, 343 passed, 24 skipped, 0 failures, 1,533 assertions (PASSED).
- **Quality Gates**:
  - `vendor/bin/pint --test`: **PASS** (0 style violations)
  - `npm run build`: **PASS** (Vite build completed in 10.44s)
  - `php artisan view:cache`: **PASS** (Blade templates cached successfully)
  - `php artisan migrate:status`: **PASS** (All 45 migrations Ran)
  - `composer validate`: **PASS** (`./composer.json is valid`)
  - `git diff --check`: **PASS** (0 whitespace errors)

## Definition of Done
Phase 21 defense scheduling implementation is complete, fully tested, hardened, and verified.
