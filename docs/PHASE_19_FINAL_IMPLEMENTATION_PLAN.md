# Phase 19 Corrected Final Implementation Plan — Official Research Forms

## Repository Baseline

- **Current Branch**: `main`
- **Current SHA**: `36f020cb66ef00701aed780f6824a81939cf73c6`
- **Git Status**: Clean baseline verified via `git status`, `git branch --show-current`, and `git log --oneline -n 20`.
- **Latest Phase 19 Commits**:
  - `36f020c` docs(phase-19): record completion pass and form matrix
  - `6e8ef6f` test(phase-19): cover versioning and actor assignment security
  - `b4a447d` feat(phase-19): add authoritative official forms workspace
  - `9f306e1` feat(official-forms): complete actor-source authorization and class form assignment system
  - `dffbd16` feat(official-forms): implement per-form payload whitelisting, source linkage validation, and print export endpoint

---

## Final Applied Corrections

### 1. RES-042 ASSIGNER AUTHORITY CORRECTION
- **Verified Backend Behavior**: `OfficialFormAuthorization::canAssignActor` evaluates assignment capability based on instance ownership scope:
  ```php
  if ($this->isSystemAdmin($assigner)) return true;

  if ($instance->research_class_group_id !== null) {
      $group = $instance->group;
      if ($group !== null && ((int)$group->adviser_id === (int)$assigner->id || (int)$group->created_by === (int)$assigner->id)) {
          return true;
      }
  }

  if ($instance->research_class_id !== null) {
      $class = $instance->researchClass;
      if ($class !== null && (int)$class->facilitator_id === (int)$assigner->id) {
          return true;
      }
  }
  return false;
  ```
- **Rule Precision**: `RES-042` is a group-owned form (`research_class_group_id !== null`, `research_class_id === null`).
  Current technical assignment authority for `RES-042` is **System Admin**, **exact Group Adviser**, or the group's **`created_by` user**. The institutional meaning of `created_by` must be verified before calling that user the Research Facilitator. Assignment authority is NOT broadened to any class Facilitator.
- **Required Unit Tests**:
  1. `test_unrelated_class_facilitator_cannot_assign_validator_to_res_042` unless that user independently satisfies group-level assignment rules (e.g. is adviser or group `created_by`).
  2. `test_exact_group_adviser_can_assign_eligible_validator_to_res_042`.
  3. `test_group_created_by_user_can_assign_validator_following_backend_rules`.
  4. `test_unrelated_faculty_cannot_assign_validator_to_res_042`.
  5. `test_student_cannot_assign_validator_to_res_042`.
  6. `test_system_admin_can_assign_validator_administratively_without_becoming_academic_validator`.

### 2. RES-029 TEMPLATE & RUNTIME RESOLUTION EVIDENCE
- **Disk Verification**: File `resources/views/pages/student/forms/res-029.blade.php` **DOES NOT EXIST** on disk.
- **Runtime Resolution Inspection**: Direct `@include($instance->definition->template_view)` in `workspace-show.blade.php` and `print.blade.php` throws a Blade `InvalidArgumentException` (`View [pages.student.forms.res-029] not found.`) if an instance of `RES-029` is rendered.
- **Corrected Classification**: **FOUNDATION / PARTIAL — MISSING TEMPLATE RUNTIME RESOLUTION**.
  - `CreateOfficialFormInstance` handles `RES-029` persistence without throwing runtime security exceptions.
  - To prevent runtime failure when rendering or printing `RES-029`, `workspace-show.blade.php` and `print.blade.php` MUST safely check `view()->exists($instance->definition->template_view)` and render a safe fallback info box without inventing unverified RES-029 response fields or fake state transitions.

---

## Architecture Verification

1. **`OfficialFormDefinition`**: Authoritative database catalog defining `code`, `title`, `ownership_scope` (`research_group` vs `research_class`), `cardinality`, `template_view`, and `sort_order`.
2. **`OfficialFormInstance`**: Single ownership model (`research_class_group_id` OR `research_class_id`), `context_key`, `source_type`/`source_id` pair, `initiated_by`, `status`, and `current_version_id`.
3. **`OfficialFormVersion`**: Immutable payload history table (`version_number`, `payload`, `created_by`, `supersedes_version_id`, `is_current`).
4. **`OfficialFormActorAssignment`**: Instance-scoped assignments (`instrument_validator`, `language_editor`, `technical_editor`, `consultant`, `panelist`) bound to `official_form_instance_id`.
5. **`ResearchClassActorAssignment`**: Class-wide authority assignments (`research_instructor`, `program_coordinator`, `dean`) scoped strictly to `research_class_id`.
6. **`OfficialFormAuthorization`**: Academic authorization engine enforcing `Permission + Exact Actor Assignment + Exact Context + Valid State`.
7. **`OfficialFormPayloadValidator`**: Whitelist schema validator rejecting forbidden system keys, unknown top-level keys, invalid types, and length violations.
8. **`OfficialFormWorkspaceController`**: Workspace management verifying visibility, handling draft/submit/actions, and managing actor assignments.
9. **`OfficialFormInstancePolicy`**: Server-side Laravel policy protecting workspace routes and print endpoints.
10. **Print Architecture**: `OfficialFormController@print` renders non-editable saved payloads via `pages.official-forms.print`.

---

## Current 7 Implemented Forms (Strict Baseline)

The following 7 forms remain strictly implemented without regression:
1. **RES-040 (Endorsement to Research Instructor)**
2. **RES-041 (Endorsement to Program Coordinator)**
3. **RES-043A (Research Instrument Item Validation)**
4. **RES-043B (Research Instrument Validation Rating)**
5. **RES-045 (Certificate of Language Editing)**
6. **RES-046 (Certificate of Technical Editing)**
7. **RES-047 (Endorsement for Reproduction)**

---

## Corrected 25-Form Matrix (Aligned with SyncOfficialFormCatalog)

| Code | Title | Scope | Cardinality | Catalog Template View | Assignment Authority | Current Status | Safe Next Work | Blocker |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| **RES-026** | Title Approval | group | single_per_group | pages.student.forms.res-026 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect `payload[date]` & `payload[topics][]`, render student roster read-only | Approval transition unverified |
| **RES-027** | Invitation to Adviser | group | single_per_group | pages.adviser.forms.res-027 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect `payload[date/course/research_title]`, derive adviser identity read-only | Adviser accept transition unverified |
| **RES-028** | Invitation to Panelist | group | per_actor | pages.panelist.forms.res-028 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload fields, derive panelist identity read-only | Panelist accept transition unverified |
| **RES-029** | Invitation to Editor | group | per_actor | pages.student.forms.res-029 | Group Adviser / Group Creator / Admin | FOUNDATION / PARTIAL | Allow foundation instance persistence; render safe template view check | Dedicated template pending |
| **RES-030** | Personnel Change | group | repeatable | pages.student.forms.res-030 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect workspace payload inputs; do NOT auto-mutate group assignments | Reassignment side-effects unverified |
| **RES-031** | Adviser Consultation | group | repeatable | pages.student.forms.res-031 | Group Adviser / Group Creator / Admin | PARTIAL | Bind workspace UI to authoritative `ConsultationRecord` source data | Adviser sign transition unverified |
| **RES-032** | Other Consultant Sheet | group | repeatable | pages.student.forms.res-032 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload inputs (`date`, `concerns`, `recommendations`, `types`), derive consultant | Consultant sign transition unverified |
| **RES-033** | Defense Endorsement | group | single_per_context | pages.adviser.forms.res-033 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload inputs (`date`, `defense_type`, `defense_date`, `time`) | Final endorsement action unverified |
| **RES-034** | Pre-Conference | group | single_per_context | pages.student.forms.res-034 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload inputs (`date`, `time`, `defense_type`, `issues`, `pages`) | Sign-off record action unverified |
| **RES-035** | Proceedings | group | single_per_context | pages.adviser.forms.res-035 | Group Adviser / Group Creator / Admin | PARTIAL | Connect payload inputs (`date`, `defense_type`, `comments`), derive title/roster | Panel evaluation action unverified |
| **RES-036** | Defense Evaluation | group | per_actor | pages.panelist.forms.res-036 | Group Adviser / Group Creator / Admin | BLOCKED — PHASE 21 | Deny runtime creation and action execution | Phase 21 Defense Panel source missing |
| **RES-037** | Evaluation Summary | group | single_per_context | pages.panelist.forms.res-037 | Group Adviser / Group Creator / Admin | BLOCKED — PHASE 21 / PHASE 22 | Deny runtime creation and action execution | Phase 21/22 Evaluation Engine missing |
| **RES-038** | Endorsement to Adviser | group | single_per_group | pages.adviser.forms.res-038 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload inputs (`date`, `day`, `month_year`), derive student roster & adviser | Sign-off chain unverified |
| **RES-039** | Revision Chart | group | single_per_context | pages.student.forms.res-039 | Group Adviser / Group Creator / Admin | PARTIAL | Bind workspace UI to authoritative review/revision source items | Panel sign-off transition unverified |
| **RES-040** | Endorsement to Instructor | group | single_per_context | pages.adviser.forms.res-040 | Group Adviser / Group Creator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-041** | Endorsement to Coordinator | class | repeatable | pages.facilitator.forms.res-041 | Class Facilitator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-042** | Validation Request | group | repeatable | pages.student.forms.res-042 | Group Adviser / Group Creator / Admin | PARTIAL | Connect payload inputs (`date`, `descriptive_title`, `course`), enable request-scoped actor assignment | Student submit action unverified |
| **RES-043A** | Item Validation | group | per_actor | pages.facilitator.forms.res-043a | Group Adviser / Group Creator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-043B** | Overall Validation Rating | group | per_actor | pages.facilitator.forms.res-043b | Group Adviser / Group Creator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-044** | Data Gathering Endorsement | group | single_per_group | pages.adviser.forms.res-044 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload inputs (`date`, `salutation`), derive title & student roster | Endorsement chain unverified |
| **RES-045** | Language Edit Certificate | group | single_per_group | pages.facilitator.forms.res-045 | Group Adviser / Group Creator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-046** | Technical Edit Certificate | group | single_per_group | pages.facilitator.forms.res-046 | Group Adviser / Group Creator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-047** | Reproduction Endorsement | group | single_per_group | pages.facilitator.forms.res-047 | Group Adviser / Group Creator / Admin | IMPLEMENTED | Preserve complete baseline and test coverage | None |
| **RES-048** | Peer Evaluation | group | single_per_group | pages.student.forms.res-048 | Group Adviser / Group Creator / Admin | PARTIAL — WORKFLOW VERIFICATION | Connect payload inputs (`evaluation_phase`, `ratings[1..4]`, `evaluation_date`) | Final acceptance transition unverified |
| **RES-049** | Authorship Certificate | group | single_per_group | pages.student.forms.res-049 | Group Adviser / Group Creator / Admin | PARTIAL — PHASE 20 SIGNATURE INTEGRATION | Connect `payload[authorship_confirmed]` boolean input, derive student roster read-only | Phase 20 Digital Signatures excluded |

---

## Template-to-Payload Audit Summary

1. **RES-026**: Payload: `payload[date]`, `payload[topics][]` (Category A). Server-Derived: `students` (Category B). Actor-Derived: Panel/Coord/Dean identities (Category D). Read-Only Display: `approved_title_number`, `remarks` (Category E).
2. **RES-027**: Payload: `payload[date]`, `payload[course]`, `payload[research_title]` (Category A). Server-Derived: Student names (Category B). Actor-Derived: Adviser, Coordinator/Dean issuer (Category D).
3. **RES-028**: Payload: `payload[date]`, `payload[panel_role]`, `payload[defense]`, `payload[course]`, `payload[defense_date]`, `payload[time]`, `payload[venue]`, `payload[research_title]` (Category A). Server-Derived: Student list (Category B). Actor-Derived: Panelist, Coordinator/Dean (Category D).
4. **RES-029**: Template missing. Add safe `view()->exists()` check in `workspace-show` and `print` to render fallback shell without inventing fake payload fields.
5. **RES-030**: Payload: `payload[date]`, `payload[degree_program]`, `payload[personnel_type][]`, `payload[current_names][]`, `payload[proposed_replacement]`, `payload[reasons]` (Category A). Server-Derived: Student names, research title (Category B).
6. **RES-031**: Payload: `payload[degree_program]`, `payload[defense_date]` (Category A). Server-Derived: `research_title`, `researchers` (Category B). Source-Derived: Consultation rows from `ConsultationRecord` (Category C).
7. **RES-032**: Payload: `payload[date]`, `payload[degree_program]`, `payload[consultant_types][]`, `payload[specific_concerns]`, `payload[recommendations]`, `payload[follow_up_date]` (Category A). Server-Derived: Student names (Category B). Actor-Derived: Consultant name (Category D).
8. **RES-033**: Payload: `payload[date]`, `payload[defense_type]`, `payload[defense_date]`, `payload[time]` (Category A). Server-Derived: Student researchers, research title (Category B). Actor-Derived: Adviser (Category D).
9. **RES-034**: Payload: `payload[date]`, `payload[time]`, `payload[defense_type]`, `payload[issues][]`, `payload[pages][]` (Category A). Server-Derived: Roster, title (Category B). Actor-Derived: Panel (Category D).
10. **RES-035**: Payload: `payload[date]`, `payload[defense_type]`, `payload[comments][]` (Category A). Server-Derived: Roster, title (Category B). Actor-Derived: Panelists (Category D).
11. **RES-038**: Payload: `payload[date]`, `payload[day]`, `payload[month_year]` (Category A). Server-Derived: Student list (Category B). Actor-Derived: Adviser, Instructor (Category D).
12. **RES-039**: Payload: `payload[date]`, `payload[recommendation]`, `payload[revisions][area][...]` (Category A). Server-Derived: Title, researchers, course (Category B). Source-Derived: Reviews from `DocumentReview`/`RevisionRequest` (Category C).
13. **RES-042**: Payload: `payload[date]`, `payload[descriptive_title]`, `payload[course]` (Category A). Server-Derived: Student names, program, major, college, title (Category B). Actor-Derived: Adviser (Category D).
14. **RES-044**: Payload: `payload[date]`, `payload[salutation]` (Category A). Server-Derived: Researchers, title, course (Category B). Actor-Derived: Adviser, Coordinator, Dean (Category D).
15. **RES-048**: Payload: `payload[evaluation_phase]`, `payload[ratings][criterion][member]` (1..4), `payload[evaluation_date]` (Category A). Server-Derived: Group member names (Category B). Actor-Derived: Evaluator (Category D).
16. **RES-049**: Payload: `payload[authorship_confirmed]` boolean (Category A). Server-Derived: Researcher names (Category B). Display: Submission date (Category E). Phase 20: Digital signatures excluded (Category F).

---

## Implementation Batches

### BATCH 1: Safe Template View Resolution & Template ↔ Payload Alignment
- In `workspace-show.blade.php` and `print.blade.php`, wrap `@include($instance->definition->template_view)` in `@if (view()->exists($instance->definition->template_view))` with a clean fallback message if the view does not exist (protecting `RES-029`).
- Connect Category A inputs to `name="payload[field]"` and `value="{{ $payload['field'] ?? '' }}"` in `res-026`, `res-027`, `res-028`, `res-030`, `res-032`, `res-033`, `res-034`, `res-035`, `res-038`, `res-044`, `res-048`, `res-049`.
- Render Category B, C, D fields read-only from `$officialFormInstance` without input names.

### BATCH 2: Authoritative Source Rendering (RES-031 & RES-039)
- **RES-031**: Render source `ConsultationRecord` rows dynamically from `$officialFormInstance->source`.
- **RES-039**: Render source `DocumentReview` / `RevisionRequest` items dynamically from `$officialFormInstance->source` and bind structured payload revisions.

### BATCH 3: RES-042 Request Payload & Exact Actor Assignment Security
- Bind RES-042 payload inputs (`date`, `descriptive_title`, `course`).
- Verify workspace actor assignment UI exposes validator assignment ONLY to authorized assigners (`isSystemAdmin`, exact `$group->adviser_id`, or `$group->created_by`).

### BATCH 4: Print Export & Safe Rendering Verification
- Verify workspace show renders updated form templates cleanly.
- Confirm `OfficialFormController@print` exports non-editable saved payloads safely.

### BATCH 5: Comprehensive Security & Validation Test Expansion
- Add tests in `StudentOfficialFormsTest.php`, `AdviserOfficialFormsTest.php`, `FacilitatorOfficialFormsTest.php`, and `OfficialFormBackendTest.php` including:
  1. `test_unrelated_class_facilitator_cannot_assign_validator_to_res_042`
  2. `test_exact_group_adviser_can_assign_eligible_validator_to_res_042`
  3. `test_group_created_by_user_can_assign_validator_following_backend_rules`
  4. `test_unrelated_faculty_cannot_assign_validator_to_res_042`
  5. `test_student_cannot_assign_validator_to_res_042`
  6. `test_system_admin_can_assign_validator_administratively_without_becoming_academic_validator`
  7. `test_res_029_safe_template_rendering_fallback`

### BATCH 6: Documentation & Final Matrix Sync
- Update `docs/PHASE_19_OFFICIAL_RESEARCH_FORMS.md` and `docs/PROJECT_PROGRESS.md`.

---

## Consistency Audit Checklist

- [x] Matrix matches `SyncOfficialFormCatalog` definitions.
- [x] Claimed actor assignment authorities match `OfficialFormAuthorization::canAssignActor`.
- [x] Candidate actor requirements match `AssignOfficialFormActor`.
- [x] RES-042 assigner authority rule strictly reflects group-owned scope (System Admin, exact Group Adviser, or Group `created_by`).
- [x] RES-029 missing template path (`pages.student.forms.res-029`) verified on disk and safe `view()->exists()` runtime handling prescribed.
