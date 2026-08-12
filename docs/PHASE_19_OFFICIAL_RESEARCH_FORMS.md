# Phase 19 — Official Research Forms

## Purpose and Scope

Phase 19 establishes the backend foundation, role-based access control (RBAC), form definition catalog, contextual form persistence, version history, and actor assignments for NDMU's 25 official research forms (`RES-026` through `RES-049`).

## Permission & Access Architecture

Official Form access is strictly permission-driven extending the existing Spatie RBAC framework.
Form permissions follow the machine-readable naming pattern: `forms.<form-code-lowercase>.<action>` (e.g. `forms.res-026.view`, `forms.res-026.submit`, `forms.res-045.certify`).

Runtime authorization is dynamically calculated:

$$\text{Allow} = \text{HasRequiredFormPermission} \land \text{HasAcademicActorAssignment} \land \text{ValidGroup/Class/DefenseScope} \land \text{ValidWorkflowState}$$

### Default Specialist Roles
- `research-instructor`: Instructor for Capstone/Research methods courses (`forms.res-040.*`, `forms.res-041.*`).
- `language-editor`: Specialist for manuscript language editing review & certification (`forms.res-029.*`, `forms.res-045.*`).
- `technical-editor`: Specialist for manuscript technical editing review & certification (`forms.res-046.*`).
- `instrument-validator`: Specialist for survey instrument item validation & rating (`forms.res-042.*`, `forms.res-043a.*`, `forms.res-043b.*`).

Form-only roles do not gain dedicated dashboards; form actions render dynamically inside the common Faculty navigation when permission requirements are satisfied.

### Explicit action authorization

Academic mutation authority is action-specific. `ApproveOfficialForm` accepts only the explicit actions `approve`, `endorse`, and `receive`; it never derives an academic action from a target status. `CertifyOfficialForm` remains the only certification transition action. An action that is absent from the configured permission map or verified workflow map fails closed.

Draft access and academic action access are intentionally separate. `initiated_by` may establish draft ownership and creator visibility, but it never grants `approve`, `endorse`, `receive`, `validate`, `certify`, `evaluate`, or `sign` authority. Academic actions require both the configured Spatie permission and the exact contextual actor source.

For class-owned forms with an actor-scoped fill action, a class facilitator is not automatically the academic actor. RES-041 initiation requires an active `research_instructor` assignment in the same class; an unrelated actor assignment such as `language_editor`, or class ownership by itself, is insufficient.

## Action-Specific Actor and Transition Matrix

| Form | Action | Permission | Academic Actor | Authoritative Source | Scope | Allowed From | Resulting State | Status | Dependency |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `RES-040` | `endorse` | `forms.res-040.endorse` | Adviser | `research_class_groups.adviser_id` | Research group | `draft`, `submitted`, `in_progress`, `pending_action` | `endorsed` | Active | None |
| `RES-040` | `receive` | `forms.res-040.receive` | Research Instructor | Active `official_form_actor_assignments` entry with `actor_type = research_instructor` for the same group | Research group | `endorsed` | `approved` | Active | Adviser endorsement |
| `RES-041` | `fill` | `forms.res-041.fill` | Research Instructor | Active `research_instructor` actor assignment within the same research class, including a group-owned form in that class | Research class | Initiation/draft editing context | `draft` or a new immutable draft version | Active | Verified same-class instructor assignment |
| `RES-041` | `endorse` | `forms.res-041.endorse` | Research Instructor | Active `research_instructor` actor assignment within the same research class | Research class | `draft`, `submitted`, `in_progress`, `pending_action` | `endorsed` | Active | None |
| `RES-041` | `receive` | `forms.res-041.receive` | Program Coordinator | Active `official_form_actor_assignments` entry with `actor_type = program_coordinator` in the same class | Research class | `endorsed` | `approved` | Active | Research Instructor endorsement |
| `RES-043A` | `validate` | `forms.res-043a.validate` | Instrument Validator | Pre-existing active `instrument_validator` assignment on the linked `RES-042` instance | Research group | Not yet implemented as a transition | Not yet implemented | Authorization/source protection active | Same-group `RES-042` source |
| `RES-043B` | `validate` | `forms.res-043b.validate` | Instrument Validator | Pre-existing active `instrument_validator` assignment on the linked `RES-042` instance | Research group | Not yet implemented as a transition | Not yet implemented | Authorization/source protection active | Same-group `RES-042` source |
| `RES-045` | `certify` | `forms.res-045.certify` | Language Editor | Active assignment with `actor_type = language_editor` for the same group | Research group | `draft`, `submitted`, `in_progress`, `pending_action` | `completed` | Active | None |
| `RES-046` | `certify` | `forms.res-046.certify` | Technical Editor | Active assignment with `actor_type = technical_editor` for the same group | Research group | `draft`, `submitted`, `in_progress`, `pending_action` | `completed` | Active | None |
| `RES-036` | `evaluate` | `forms.res-036.evaluate` | Panelist | Authoritative defense panel assignment | Research group/defense | None | None | Blocked | Phase 21 defense panel assignment |
| `RES-037` | `sign` | `forms.res-037.sign` | Panelist | Authoritative defense panel/evaluation summary source | Research group/defense | None | None | Blocked | Phase 21/22 panel and evaluation data |

Generic `approve` is deliberately not configured for RES-040 or RES-041 and cannot substitute for `receive`. Wrong-order transitions are rejected inside the database transaction after the form instance is locked.

## Catalog Schema & Models

- `official_form_definitions`: `id`, `code` (unique, e.g. `RES-026`), `title`, `description`, `default_category`, `ownership_scope` (`research_group` vs `research_class`), `cardinality` (`single_per_group`, `single_per_context`, `per_actor`, `repeatable`), `template_view`, `is_active`, `sort_order`, `metadata`.
- `official_form_instances`: `id`, `official_form_definition_id`, `research_class_group_id` (nullable), `research_class_id` (nullable), `context_key` (`general`, `proposal_defense`, `final_defense`, `proposal_revision`, `final_paper_revision`), `source_type` (nullable), `source_id` (nullable), `initiated_by`, `status`, `current_version_id`.
- `official_form_versions`: `id`, `official_form_instance_id`, `version_number`, `payload` (JSON), `created_by`, `supersedes_version_id`, `is_current`.
- `official_form_actor_assignments`: `id`, `official_form_instance_id`, `user_id`, `actor_type` (`adviser`, `panelist`, `language_editor`, `technical_editor`, `instrument_validator`, `research_instructor`, `program_coordinator`, `dean`, `consultant`), `assigned_by`, `assigned_at`, `status`.

## Authoritative Actor-Source Matrix

| Form Code | Actor Type | Authoritative Actor Source | Source Model / FK | Owner Scope | Context Key | Source Exists? | Phase Owner | Integration Status | Blocked Dependency |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `RES-026` | Student / Adviser | `ResearchClassGroup` | `leader_student_id` / `adviser_id` | Research Group | `general` | Yes | Phase 12 | Active | None |
| `RES-027` | Adviser | `ResearchClassGroup` | `adviser_id` | Research Group | `general` | Yes | Phase 12 | Active | None |
| `RES-028` | Panelist | `OfficialFormActorAssignment` | `official_form_actor_assignments` | Research Group | `defense` | Yes | Phase 19 | Active | None |
| `RES-029` | Language Editor | `OfficialFormActorAssignment` | `official_form_actor_assignments` | Research Group | `editing` | Yes | Phase 19 | Active | None |
| `RES-031` | Adviser | `ConsultationRecord` | `consultation_records` | Research Group | `general` | Yes | Phase 16 | Active | None |
| `RES-032` | Specialist Consultant | `OfficialFormActorAssignment` | `official_form_actor_assignments` | Research Group | `specialist` | Yes | Phase 19 | Active | None |
| `RES-033` | Adviser | `ResearchClassGroup` | `adviser_id` | Research Group | `proposal_defense` | Yes | Phase 12 | Active | None |
| `RES-036` | Panelist | Defense Panel Assignment | `defense_schedules` / `evaluations` | Research Group | `defense` | No | Phase 21 | Blocked | Blocked pending Phase 21 Defense Panel Assignment |
| `RES-037` | Panelist | Defense Panel Assignment | `defense_schedules` / `evaluations` | Research Group | `defense` | No | Phase 21 / 22 | Blocked | Blocked pending Phase 21/22 Panel Summaries |
| `RES-038` | Adviser / Facilitator | `ResearchClass` | `facilitator_id` / `adviser_id` | Research Class | `endorsement` | Yes | Phase 10 / 12 | Active | None |
| `RES-039` | Adviser / Group | `DocumentReview` / `RevisionRequest` | `document_reviews` / `revision_requests` | Research Group | `revision` | Yes | Phase 15 / 17 | Active | None |
| `RES-040` | Research Instructor | Specialist Role Assignment | `official_form_actor_assignments` | Research Class | `endorsement` | Yes | Phase 19 | Active | None |
| `RES-041` | Program Coordinator | Specialist Role Assignment | `official_form_actor_assignments` | Research Class | `endorsement` | Yes | Phase 19 | Active | None |
| `RES-042` | Student / Validator | `OfficialFormInstance` | `official_form_instances` (`RES-042`) | Research Group | `validation` | Yes | Phase 19 | Active | None |
| `RES-043A` | Instrument Validator | `RES-042` Validation Request | `source_type` = `OfficialFormInstance` (`RES-042`) | Research Group | `validation` | Yes | Phase 19 | Active | Linked to `RES-042` source |
| `RES-043B` | Instrument Validator | `RES-042` Validation Request | `source_type` = `OfficialFormInstance` (`RES-042`) | Research Group | `validation` | Yes | Phase 19 | Active | Linked to `RES-042` source |
| `RES-044` | Adviser | `ResearchClassGroup` | `adviser_id` | Research Group | `final_defense` | Yes | Phase 12 | Active | None |
| `RES-045` | Language Editor | `OfficialFormActorAssignment` | `actor_type` = `language_editor` | Research Group | `editing` | Yes | Phase 19 | Active | None |
| `RES-046` | Technical Editor | `OfficialFormActorAssignment` | `actor_type` = `technical_editor` | Research Group | `editing` | Yes | Phase 19 | Active | None |
| `RES-047` | Facilitator / Dean | `ResearchClass` | `facilitator_id` | Research Class | `approval` | Yes | Phase 10 | Active | None |

## Cross-Phase Integrations
- **Phase 16 (Consultations)**: `RES-031` integrates with completed academic records from `consultation_records`. `RES-032` represents external/specialist consultations.
- **Phase 15 & 17 (Revisions)**: `RES-039` aggregates Phase 15 document review findings (`document_review_findings`) and Phase 17 revision cycle responses (`revision_requests`).
- **Phase 18 (Progress Milestones)**: Form approval does not automatically complete Phase 18 milestones. Progress transitions remain under explicit facilitator control.
- **Phase 20 (Digital Signatures)**: Phase 19 stores authoritative version payloads so Phase 20 can later attach digital signatures and QR verification hashes.

## Generic Authorization Cleanup Verification

- `OfficialFormBackendTest`: 16 tests, 36 assertions, 0 failures.
- `FormPermissionsTest`: 5 tests, 16 assertions, 0 failures.
- Complete Official Forms feature suite: 33 tests, 231 assertions, 0 failures.
- Full regression: 289 tests, 1,256 assertions, 257 passed, 9 failed, 23 skipped. The remaining failures are outside this cleanup (legacy admin/dashboard expectations, Phase 20 signature routes intentionally returning `410`, and an existing student milestone assertion).
- Laravel Pint: passed after formatting the changed Phase 19 files.
- Vite production build: passed (58 modules transformed).
- Migration status: all listed migrations ran; this cleanup adds no migration.

Phase 19 remains **In Progress**. The next work is the direct, evidence-based per-form implementation sequence beginning with RES-026; no further generic authorization redesign is planned.
