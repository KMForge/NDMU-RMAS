# Phase 12 — Research Groups and Adviser Assignment

## 1. Purpose

Phase 12 rebuilds the research group management and adviser assignment foundation for NDMU-RMAS. It allows Research Facilitators to create capstone research groups within their owned research classes, assign active enrolled students to groups (enforcing a strict maximum of 4 students per group), move students between groups, disband groups cleanly, send adviser requests to eligible faculty, and manage adviser changes. It also empowers Research Advisers to review and accept/decline adviser requests and view assigned group members in their "My Classes" workspace, while giving Student Researchers visibility into their own group, group mates, and accepted adviser.

## 2. Scope

### Implemented in Phase 12:
- Facilitator creation of research groups inside owned Research Classes.
- Facilitator assignment of active enrolled students (`ResearchClassEnrollment` where `status = active`) to groups.
- Move student flow (updates existing group membership row without creating duplicates).
- Unassigned Students query and display section.
- Disband group workflow (detaches members returning them to Unassigned Students list, cancels pending adviser requests, closes active adviser assignment history, and marks group as disbanded).
- Adviser invitation request workflow (`pending`, `accepted`, `declined`, `cancelled`).
- One pending adviser request per group limit.
- One active adviser per group limit.
- Adviser response flow (Accept/Decline).
- Adviser "My Classes" sidebar count badge for pending requests.
- Adviser "My Classes" workspace featuring pending request cards with Accept/Decline confirmation modals.
- Adviser view of assigned research group cards with student member names (data minimization boundary).
- Neutral "No title selected yet" display placeholder for future Title Proposal integration.
- Facilitator cancellation of pending adviser request.
- Facilitator removal/change of accepted adviser with assignment history preservation.
- Scoped visibility: Facilitator sees all owned class groups; Adviser sees only accepted assigned groups and members; Student sees only their own group, mates, and accepted adviser.
- Automated feature test suite in `tests/Feature/Classes/ResearchClassGroupWorkflowTest.php`.

### Deferred / Excluded from Phase 12:
- Document uploads and private storage (Phase 13)
- Research repository search and document downloads (Phase 14)
- Adviser document reviews and comments (Phase 15)
- Consultation bookings (Phase 16)
- Revision tracking (Phase 17)
- Title Proposal backend workflow (Phase 15+)
- Defense scheduling, evaluation, digital signatures, and reports (Phases 18+)

## 3. Actors

- **Research Facilitator**: Owns the Research Class; creates groups, assigns/moves students, disbands groups, sends/cancels adviser requests, and removes advisers.
- **Thesis Adviser**: Receives adviser invitation requests, accepts or declines requests via My Classes workspace, and views assigned groups and student member names.
- **Student Researcher**: Views their assigned group, group mates, and accepted adviser on the class details page.

## 4. Preconditions

- Account must be authenticated, email verified, and active/approved (`status = active`, `approved_at != null`).
- Facilitator must possess `dashboards.facilitator.view`, `classes.view-own`, `classes.manage-groups`, and `classes.assign-advisers` permissions.
- Adviser must possess `dashboards.adviser.view` and `classes.serve-as-adviser` permissions.
- Student must possess `dashboards.student.view` and `classes.view-enrolled` permissions with an active class enrollment.

## 5. Confirmed Business Rules

1. **Group Ownership**: Only the facilitator owning the research class can manage groups and adviser assignments for that class.
2. **Group Name**: Manually entered by facilitator, trimmed, required (2–120 chars), and unique within active groups of the class.
3. **Group Size**: Strict limit of 1 to 4 students per group. The backend enforces an absolute hard maximum of 4 members in database transactions.
4. **Active Student Eligibility**: Only students with an active `ResearchClassEnrollment` in that class can be assigned to a group. Pending or rejected join requests cannot be assigned.
5. **One Group Per Student**: A student may belong to only ONE group inside their active class. Moving a student updates the existing `ResearchClassGroupMember` record without generating duplicate rows.
6. **Unassigned Students**: Active enrolled students not assigned to any group appear in the Unassigned Students list.
7. **Disband Group**: Disbanding a group detaches member records so students return to the Unassigned Students list as active class members, cancels any pending adviser request, ends active adviser history, and marks the group status as `disbanded`.
8. **Adviser Invitation Flow**: Adviser assignment requires explicit invitation and acceptance. The facilitator sends a request to an active/approved adviser with `classes.serve-as-adviser`. The group `adviser_id` remains `null` until the adviser accepts.
9. **One Pending Adviser Request**: Only ONE pending adviser request is allowed per group at a time.
10. **One Active Adviser**: One research group may have at most ONE active adviser at a time. An adviser may advise multiple groups.
11. **Changing/Removing Adviser**: Removing an adviser closes the active history entry (`ended_at = now()`, `ended_by = facilitator`), sets `adviser_id = null`, and allows sending a new adviser request.
12. **Adviser My Classes Workspace & Pending Badge**:
   - The "My Classes" sidebar displays a badge containing the exact count of `pending` adviser requests for the logged-in adviser.
   - If count is 0, the badge is hidden.
   - Clicking "My Classes" opens the workspace showing Pending Adviser Requests and My Assigned Research Groups.
13. **Member Name Visibility**:
   - An adviser can view full student member names ONLY for groups where they are the current accepted adviser.
14. **Visibility Scoping**:
   - Facilitator: sees all groups in owned classes.
   - Adviser: sees only groups where their request was accepted and they are the active adviser.
   - Student: sees only their own group name, mates, and accepted adviser.

## 6. Database Model & Schema Changes

New reversible migration `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php`:
- Added `status` (`default 'active'`) and `disbanded_at` (`nullable timestampTz`) to `research_class_groups`.
- Created `research_class_group_adviser_requests`:
  - `id`, `research_class_group_id`, `adviser_id`, `requested_by`, `status` (`pending`, `accepted`, `declined`, `cancelled`), `requested_at`, `responded_at`, `cancelled_at`, `timestampsTz`.
- Created `research_class_group_adviser_histories`:
  - `id`, `research_class_group_id`, `adviser_id`, `assigned_by`, `assigned_at`, `ended_at`, `ended_by`, `timestampsTz`.

## 7. Routes and Endpoints

### Facilitator Routes (`routes/facilitator.php`):
- `POST /facilitator/classes/{researchClass}/groups` -> `facilitator.classes.groups.store`
- `PATCH /facilitator/classes/{researchClass}/groups/{group}/rename` -> `facilitator.classes.groups.rename`
- `DELETE /facilitator/classes/{researchClass}/groups/{group}` -> `facilitator.classes.groups.disband`
- `PUT /facilitator/classes/{researchClass}/groups/{group}/students/{enrollment}` -> `facilitator.classes.groups.students.assign`
- `POST /facilitator/classes/{researchClass}/groups/{group}/adviser-requests` -> `facilitator.classes.groups.adviser-requests.store`
- `DELETE /facilitator/classes/{researchClass}/groups/{group}/adviser-requests/{adviserRequest}` -> `facilitator.classes.groups.adviser-requests.cancel`
- `DELETE /facilitator/classes/{researchClass}/groups/{group}/adviser` -> `facilitator.classes.groups.adviser.remove`

### Adviser Routes (`routes/adviser.php`):
- `PATCH /adviser/group-requests/{adviserRequest}/respond` -> `adviser.group-requests.respond`

## 8. Controllers and Actions

- `CreateResearchClassGroup`: Handles cache locking, transaction, uniqueness check, and group creation.
- `AssignStudentToResearchClassGroup`: Handles active enrollment check, max 4 members check, transaction lock, and single membership row creation/update.
- `DisbandResearchClassGroup`: Handles transactional disbanding, pending request cancellation, adviser history closing, member detachment, and status transition.
- `RequestAdviserForResearchClassGroup`: Handles adviser eligibility check, pending request lock check, and request creation.
- `CancelResearchClassGroupAdviserRequest`: Cancels pending adviser request.
- `RespondResearchGroupAdviserRequest`: Handles adviser accept/decline decision inside a transaction, setting `adviser_id` and creating history on acceptance.
- `RemoveResearchClassGroupAdviser`: Closes active adviser history and sets `adviser_id` to null.
- `RenameResearchClassGroup`: Updates group name safely.
- `DashboardController` (Adviser): Loads `$pendingAdviserRequests` (with group, class, members, requester) and `$assignedGroups` (with class, members, student) for the logged-in adviser.
- `ResearchGroupAdviserRequestController`: Processes accept/decline responses and redirects to `to_route('adviser.dashboard', ['tab' => 'classes'])`.

## 9. Security & Authorization

- Route middleware enforces `auth`, `verified`, `active`, and Spatie permission checks (`dashboards.adviser.view`, `classes.serve-as-adviser`).
- Adviser response action validates that `$adviserRequest->adviser_id === $authenticatedUser->id` to prevent IDOR attacks.
- Adviser response action locks group and request records within a database transaction, verifying status is still `pending` and group is `active`.
- Group member student details are exposed only to the accepted active adviser for that specific group.

## 10. Files Changed / Created

| File | Type | Purpose |
| --- | --- | --- |
| `database/migrations/2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php` | NEW | Migration for adviser requests, history, and group status tracking. |
| `app/Models/ResearchClassGroupAdviserRequest.php` | NEW | Model for adviser requests. |
| `app/Models/ResearchClassGroupAdviserHistory.php` | NEW | Model for adviser assignment history. |
| `app/Models/ResearchClassGroup.php` | MODIFY | Added status, disband timestamps, adviser requests, and history relations. |
| `app/Modules/Classes/Actions/CreateResearchClassGroup.php` | MODIFY | Added active status check and 403 ownership protection. |
| `app/Modules/Classes/Actions/AssignStudentToResearchClassGroup.php` | MODIFY | Enforced max 4 members limit and student move logic. |
| `app/Modules/Classes/Actions/DisbandResearchClassGroup.php` | NEW | Action to disband group and return members to unassigned. |
| `app/Modules/Classes/Actions/RequestAdviserForResearchClassGroup.php` | NEW | Action to send adviser invitation request. |
| `app/Modules/Classes/Actions/CancelResearchClassGroupAdviserRequest.php` | NEW | Action to cancel pending adviser request. |
| `app/Modules/Classes/Actions/RespondResearchGroupAdviserRequest.php` | NEW | Action for adviser to accept or decline request. |
| `app/Modules/Classes/Actions/RemoveResearchClassGroupAdviser.php` | NEW | Action to remove accepted adviser and record history. |
| `app/Modules/Classes/Actions/RenameResearchClassGroup.php` | NEW | Action to rename group. |
| `app/Modules/Classes/Queries/GetFacilitatorClassData.php` | MODIFY | Added active groups count and populated class adviser options. |
| `app/Http/Controllers/Facilitator/ResearchClassGroupController.php` | NEW | Controller for facilitator group and adviser management. |
| `app/Http/Controllers/Adviser/ResearchGroupAdviserRequestController.php` | MODIFY | Controller for adviser request responses with redirect to `?tab=classes`. |
| `app/Http/Controllers/Facilitator/ResearchClassController.php` | MODIFY | Updated show() to pass active groups, unassigned students, and adviser options. |
| `app/Http/Controllers/Student/ResearchClassController.php` | MODIFY | Updated show() to pass student's active group, mates, and accepted adviser. |
| `app/Http/Controllers/Adviser/DashboardController.php` | MODIFY | Updated dashboard to load pending requests and assigned groups with members. |
| `app/Policies/ResearchClassPolicy.php` | MODIFY | Added active group status check for assigned adviser visibility. |
| `resources/views/pages/facilitator-class-details.blade.php` | MODIFY | Updated Blade view with group cards, adviser requests, and unassigned roster. |
| `resources/views/pages/adviser-dashboard.blade.php` | MODIFY | Refined My Classes workspace, pending badge, Accept/Decline modals, member lists, and title placeholder. |
| `routes/facilitator.php` | MODIFY | Connected Phase 12 facilitator group & adviser routes. |
| `routes/adviser.php` | MODIFY | Connected Phase 12 adviser request response route. |
| `tests/Feature/Classes/ResearchClassGroupWorkflowTest.php` | MODIFY | Reactivated and expanded test suite for all Phase 12 and Adviser My Classes features. |
| `docs/PHASE_12_RESEARCH_GROUPS_AND_ADVISER_ASSIGNMENT.md` | MODIFY | Technical documentation for Phase 12. |

## 11. Verification and Test Results

### Commands Executed:
```bash
php artisan migrate
php artisan test tests/Feature/Classes
vendor/bin/pint --test
npm run build
```

### Verification Outcome:
- **Phase 10, 11 & 12 Class Tests**: 32 tests passed cleanly (`100%` pass rate, `0` failures).
- **Phase 12 Group & Adviser Workspace Tests**: 17 focused tests passed (`0` failures).
- **Pint Formatting**: Passed (`vendor/bin/pint --test`).
- **Vite Build**: Compiled client assets successfully (`npm run build`).

## 12. Recommended Phase Status

**Completed**
