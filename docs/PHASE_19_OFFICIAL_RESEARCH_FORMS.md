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

## Cross-Phase Integrations
- **Phase 16 (Consultations)**: `RES-031` integrates with completed academic records from `consultation_records`. `RES-032` represents external/specialist consultations.
- **Phase 15 & 17 (Revisions)**: `RES-039` aggregates Phase 15 document review findings (`document_review_findings`) and Phase 17 revision cycle responses (`revision_requests`).
- **Phase 18 (Progress Milestones)**: Form approval does not automatically complete Phase 18 milestones. Progress transitions remain under explicit facilitator control.
- **Phase 20 (Digital Signatures)**: Phase 19 stores authoritative version payloads so Phase 20 can later attach digital signatures and QR verification hashes.
