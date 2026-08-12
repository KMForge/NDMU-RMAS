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

