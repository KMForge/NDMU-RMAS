# NDMU-RMAS Database Reference

> Generated from the live PostgreSQL system catalog, Laravel runtime configuration, the applied `migrations` ledger, migration files, and Eloquent model metadata. No table, column, relationship, constraint, policy, index, or count in this document was invented manually.

Generated at: `2026-08-24 10:21:17 UTC`

## Verification scope

- Live source: PostgreSQL database `ndmu_rmas`, schema `public`.
- Connection driver: `pgsql`.
- Server-reported database: `ndmu_rmas`.
- Server-reported current role: `postgres`.
- Server version: `PostgreSQL 17.11 on x86_64-pc-linux-musl, compiled by gcc (Alpine 15.2.0) 15.2.0, 64-bit`.
- Secrets are intentionally excluded. Passwords, hashes, tokens, file paths, and row contents are not documented.
- Row counts are a point-in-time snapshot and will change as the application is used.

## Safe connection profile

| Setting | Verified value |
| --- | --- |
| Laravel connection | `pgsql` |
| Driver | `pgsql` |
| Host | `127.0.0.1` |
| Port | `5432` |
| Database | `ndmu_rmas` |
| Username | `postgres` |
| Schema search path | `public` |
| SSL mode | `prefer` |

## Catalog summary

| Item | Count |
| --- | ---: |
| Public tables | 74 |
| Public table columns | 728 |
| Public views | 0 |
| Foreign keys | 170 |
| Primary keys | 74 |
| Unique constraints | 56 |
| Check constraints | 1 |
| Indexes, including constraint indexes | 238 |
| Tables with RLS enabled | 66 |
| RLS policies | 0 |
| User-defined public triggers | 0 |
| Public functions | 0 |
| Public sequences | 65 |
| Applied migrations | 50 |
| Eloquent models mapped to live tables | 52 |

## Table inventory and current row counts

| Table | Rows | Columns | RLS | Owner |
| --- | ---: | ---: | --- | --- |
| `academic_terms` | 5 | 8 | Yes | `postgres` |
| `academic_years` | 2 | 7 | Yes | `postgres` |
| `adviser_assignments` | 0 | 10 | Yes | `postgres` |
| `audit_logs` | 45 | 15 | No | `postgres` |
| `cache` | 0 | 3 | Yes | `postgres` |
| `cache_locks` | 0 | 3 | Yes | `postgres` |
| `colleges` | 1 | 6 | Yes | `postgres` |
| `consultation_attendances` | 0 | 6 | No | `postgres` |
| `consultation_audits` | 0 | 11 | No | `postgres` |
| `consultation_records` | 0 | 18 | No | `postgres` |
| `consultation_requests` | 0 | 26 | Yes | `postgres` |
| `consultation_schedule_proposals` | 0 | 11 | No | `postgres` |
| `defense_evaluation_round_panelists` | 0 | 6 | Yes | `postgres` |
| `defense_evaluation_round_students` | 0 | 6 | Yes | `postgres` |
| `defense_evaluation_rounds` | 0 | 14 | Yes | `postgres` |
| `defense_evaluation_student_scores` | 0 | 10 | Yes | `postgres` |
| `defense_evaluation_student_summaries` | 0 | 6 | Yes | `postgres` |
| `defense_evaluation_summaries` | 0 | 9 | Yes | `postgres` |
| `defense_evaluations` | 0 | 14 | Yes | `postgres` |
| `defense_panel_assignments` | 6 | 10 | Yes | `postgres` |
| `defense_rooms` | 1 | 7 | Yes | `postgres` |
| `defense_schedules` | 2 | 11 | Yes | `postgres` |
| `defenses` | 2 | 10 | Yes | `postgres` |
| `departments` | 1 | 7 | Yes | `postgres` |
| `document_access_audits` | 14 | 9 | No | `postgres` |
| `document_review_audits` | 4 | 11 | Yes | `postgres` |
| `document_review_comments` | 1 | 11 | Yes | `postgres` |
| `document_reviews` | 3 | 11 | Yes | `postgres` |
| `document_upload_audits` | 4 | 11 | Yes | `postgres` |
| `documents` | 3 | 20 | Yes | `postgres` |
| `faculty_profiles` | 0 | 9 | Yes | `postgres` |
| `failed_jobs` | 0 | 7 | Yes | `postgres` |
| `job_batches` | 0 | 10 | Yes | `postgres` |
| `jobs` | 0 | 7 | Yes | `postgres` |
| `migrations` | 50 | 3 | Yes | `postgres` |
| `milestone_definitions` | 13 | 9 | Yes | `postgres` |
| `milestone_evidences` | 3 | 9 | Yes | `postgres` |
| `model_has_permissions` | 0 | 3 | Yes | `postgres` |
| `model_has_roles` | 34 | 3 | Yes | `postgres` |
| `notifications` | 0 | 8 | Yes | `postgres` |
| `official_form_actor_assignments` | 0 | 9 | Yes | `postgres` |
| `official_form_definitions` | 25 | 13 | Yes | `postgres` |
| `official_form_instances` | 1 | 13 | Yes | `postgres` |
| `official_form_signatures` | 5 | 20 | Yes | `postgres` |
| `official_form_verifications` | 1 | 5 | Yes | `postgres` |
| `official_form_versions` | 2 | 10 | Yes | `postgres` |
| `password_reset_tokens` | 0 | 3 | Yes | `postgres` |
| `permissions` | 113 | 9 | Yes | `postgres` |
| `programs` | 8 | 8 | Yes | `postgres` |
| `research_class_actor_assignments` | 3 | 9 | Yes | `postgres` |
| `research_class_enrollments` | 4 | 10 | Yes | `postgres` |
| `research_class_group_adviser_histories` | 1 | 9 | Yes | `postgres` |
| `research_class_group_adviser_requests` | 3 | 10 | Yes | `postgres` |
| `research_class_group_member_histories` | 0 | 11 | No | `postgres` |
| `research_class_group_members` | 4 | 8 | Yes | `postgres` |
| `research_class_groups` | 2 | 12 | Yes | `postgres` |
| `research_classes` | 1 | 11 | Yes | `postgres` |
| `research_group_members` | 3 | 8 | Yes | `postgres` |
| `research_group_milestone_events` | 6 | 13 | Yes | `postgres` |
| `research_group_milestones` | 26 | 14 | Yes | `postgres` |
| `research_groups` | 1 | 7 | Yes | `postgres` |
| `research_projects` | 1 | 13 | Yes | `postgres` |
| `research_proposals` | 0 | 13 | Yes | `postgres` |
| `revision_request_events` | 0 | 13 | Yes | `postgres` |
| `revision_requests` | 0 | 18 | Yes | `postgres` |
| `role_has_permissions` | 306 | 2 | Yes | `postgres` |
| `roles` | 18 | 9 | Yes | `postgres` |
| `sessions` | 0 | 6 | Yes | `postgres` |
| `signature_audits` | 4 | 9 | Yes | `postgres` |
| `student_profiles` | 3 | 8 | Yes | `postgres` |
| `system_settings` | 1 | 9 | No | `postgres` |
| `title_presentations` | 1 | 15 | Yes | `postgres` |
| `user_signatures` | 4 | 11 | Yes | `postgres` |
| `users` | 28 | 15 | Yes | `postgres` |

## Relationship map

| Source table | Constraint | PostgreSQL definition |
| --- | --- | --- |
| `academic_terms` | `academic_terms_academic_year_id_foreign` | `FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE` |
| `adviser_assignments` | `adviser_assignments_adviser_id_foreign` | `FOREIGN KEY (adviser_id) REFERENCES faculty_profiles(id) ON DELETE RESTRICT` |
| `adviser_assignments` | `adviser_assignments_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `adviser_assignments` | `adviser_assignments_research_project_id_foreign` | `FOREIGN KEY (research_project_id) REFERENCES research_projects(id) ON DELETE CASCADE` |
| `audit_logs` | `audit_logs_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_attendances` | `consultation_attendances_consultation_record_id_foreign` | `FOREIGN KEY (consultation_record_id) REFERENCES consultation_records(id) ON DELETE CASCADE` |
| `consultation_attendances` | `consultation_attendances_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_audits` | `consultation_audits_actor_id_foreign` | `FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_audits` | `consultation_audits_consultation_request_id_foreign` | `FOREIGN KEY (consultation_request_id) REFERENCES consultation_requests(id) ON DELETE CASCADE` |
| `consultation_audits` | `consultation_audits_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `consultation_records` | `consultation_records_conducted_by_foreign` | `FOREIGN KEY (conducted_by) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_records` | `consultation_records_consultation_request_id_foreign` | `FOREIGN KEY (consultation_request_id) REFERENCES consultation_requests(id) ON DELETE CASCADE` |
| `consultation_records` | `consultation_records_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `consultation_records` | `consultation_records_supersedes_record_id_foreign` | `FOREIGN KEY (supersedes_record_id) REFERENCES consultation_records(id) ON DELETE SET NULL` |
| `consultation_requests` | `consultation_requests_adviser_assignment_id_foreign` | `FOREIGN KEY (adviser_assignment_id) REFERENCES adviser_assignments(id) ON DELETE CASCADE` |
| `consultation_requests` | `consultation_requests_assigned_adviser_id_foreign` | `FOREIGN KEY (assigned_adviser_id) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_requests` | `consultation_requests_cancelled_by_foreign` | `FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_requests` | `consultation_requests_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `consultation_requests` | `consultation_requests_requested_by_foreign` | `FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_requests` | `consultation_requests_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE SET NULL` |
| `consultation_requests` | `consultation_requests_research_project_id_foreign` | `FOREIGN KEY (research_project_id) REFERENCES research_projects(id) ON DELETE CASCADE` |
| `consultation_requests` | `consultation_requests_reviewed_by_foreign` | `FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_schedule_proposals` | `consultation_schedule_proposals_consultation_request_id_foreign` | `FOREIGN KEY (consultation_request_id) REFERENCES consultation_requests(id) ON DELETE CASCADE` |
| `consultation_schedule_proposals` | `consultation_schedule_proposals_proposed_by_foreign` | `FOREIGN KEY (proposed_by) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_schedule_proposals` | `consultation_schedule_proposals_responded_by_foreign` | `FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL` |
| `defense_evaluation_round_panelists` | `defense_evaluation_round_panelists_defense_evaluation_round_id_` | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE CASCADE` |
| `defense_evaluation_round_panelists` | `defense_evaluation_round_panelists_defense_panel_assignment_id_` | `FOREIGN KEY (defense_panel_assignment_id) REFERENCES defense_panel_assignments(id) ON DELETE RESTRICT` |
| `defense_evaluation_round_panelists` | `defense_evaluation_round_panelists_panelist_user_id_foreign` | `FOREIGN KEY (panelist_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_round_students` | `defense_evaluation_round_students_defense_evaluation_round_id_f` | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE CASCADE` |
| `defense_evaluation_round_students` | `defense_evaluation_round_students_group_member_id_foreign` | `FOREIGN KEY (group_member_id) REFERENCES research_class_group_members(id) ON DELETE SET NULL` |
| `defense_evaluation_round_students` | `defense_evaluation_round_students_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds` | `defense_evaluation_rounds_defense_id_foreign` | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds` | `defense_evaluation_rounds_defense_schedule_id_foreign` | `FOREIGN KEY (defense_schedule_id) REFERENCES defense_schedules(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds` | `defense_evaluation_rounds_opened_by_foreign` | `FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds` | `defense_evaluation_rounds_released_by_foreign` | `FOREIGN KEY (released_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds` | `defense_evaluation_rounds_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds` | `defense_evaluation_rounds_summary_signer_user_id_foreign` | `FOREIGN KEY (summary_signer_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_scores` | `defense_evaluation_student_scores_defense_evaluation_id_foreign` | `FOREIGN KEY (defense_evaluation_id) REFERENCES defense_evaluations(id) ON DELETE CASCADE` |
| `defense_evaluation_student_scores` | `defense_evaluation_student_scores_round_student_id_foreign` | `FOREIGN KEY (round_student_id) REFERENCES defense_evaluation_round_students(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_scores` | `defense_evaluation_student_scores_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_summaries` | `defense_evaluation_student_summaries_defense_evaluation_summary` | `FOREIGN KEY (defense_evaluation_summary_id) REFERENCES defense_evaluation_summaries(id) ON DELETE CASCADE` |
| `defense_evaluation_student_summaries` | `defense_evaluation_student_summaries_round_student_id_foreign` | `FOREIGN KEY (round_student_id) REFERENCES defense_evaluation_round_students(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_summaries` | `defense_evaluation_student_summaries_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_summaries` | `defense_evaluation_summaries_defense_evaluation_round_id_foreig` | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE RESTRICT` |
| `defense_evaluations` | `defense_evaluations_defense_evaluation_round_id_foreign` | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE RESTRICT` |
| `defense_evaluations` | `defense_evaluations_panelist_user_id_foreign` | `FOREIGN KEY (panelist_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluations` | `defense_evaluations_round_panelist_id_foreign` | `FOREIGN KEY (round_panelist_id) REFERENCES defense_evaluation_round_panelists(id) ON DELETE RESTRICT` |
| `defense_panel_assignments` | `defense_panel_assignments_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_panel_assignments` | `defense_panel_assignments_defense_id_foreign` | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `defense_panel_assignments` | `defense_panel_assignments_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_schedules` | `defense_schedules_defense_id_foreign` | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `defense_schedules` | `defense_schedules_room_id_foreign` | `FOREIGN KEY (room_id) REFERENCES defense_rooms(id) ON DELETE RESTRICT` |
| `defense_schedules` | `defense_schedules_scheduled_by_foreign` | `FOREIGN KEY (scheduled_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_schedules` | `defense_schedules_supersedes_schedule_id_foreign` | `FOREIGN KEY (supersedes_schedule_id) REFERENCES defense_schedules(id) ON DELETE RESTRICT` |
| `defenses` | `defenses_completed_by_foreign` | `FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `defenses` | `defenses_created_by_foreign` | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defenses` | `defenses_current_schedule_id_foreign` | `FOREIGN KEY (current_schedule_id) REFERENCES defense_schedules(id) ON DELETE SET NULL` |
| `defenses` | `defenses_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `departments` | `departments_college_id_foreign` | `FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE RESTRICT` |
| `document_access_audits` | `document_access_audits_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE RESTRICT` |
| `document_access_audits` | `document_access_audits_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `document_review_audits` | `document_review_audits_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE` |
| `document_review_audits` | `document_review_audits_reviewer_id_foreign` | `FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `document_review_audits` | `document_review_audits_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `document_review_comments` | `document_review_comments_author_id_foreign` | `FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `document_review_comments` | `document_review_comments_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE` |
| `document_review_comments` | `document_review_comments_parent_id_foreign` | `FOREIGN KEY (parent_id) REFERENCES document_review_comments(id) ON DELETE CASCADE` |
| `document_review_comments` | `document_review_comments_resolved_by_foreign` | `FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL` |
| `document_reviews` | `document_reviews_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE` |
| `document_reviews` | `document_reviews_reviewer_id_foreign` | `FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `document_reviews` | `document_reviews_supersedes_review_id_foreign` | `FOREIGN KEY (supersedes_review_id) REFERENCES document_reviews(id) ON DELETE SET NULL` |
| `document_upload_audits` | `document_upload_audits_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `document_upload_audits` | `document_upload_audits_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE SET NULL` |
| `document_upload_audits` | `document_upload_audits_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `documents` | `documents_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `documents` | `documents_revision_request_id_foreign` | `FOREIGN KEY (revision_request_id) REFERENCES revision_requests(id) ON DELETE SET NULL` |
| `documents` | `documents_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `faculty_profiles` | `faculty_profiles_department_id_foreign` | `FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT` |
| `faculty_profiles` | `faculty_profiles_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `milestone_evidences` | `milestone_evidences_linked_by_foreign` | `FOREIGN KEY (linked_by) REFERENCES users(id) ON DELETE SET NULL` |
| `milestone_evidences` | `milestone_evidences_research_group_milestone_id_foreign` | `FOREIGN KEY (research_group_milestone_id) REFERENCES research_group_milestones(id) ON DELETE RESTRICT` |
| `model_has_permissions` | `model_has_permissions_permission_id_foreign` | `FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE` |
| `model_has_roles` | `model_has_roles_role_id_foreign` | `FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE` |
| `official_form_actor_assignments` | `official_form_actor_assignments_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `official_form_actor_assignments` | `official_form_actor_assignments_official_form_instance_id_forei` | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `official_form_actor_assignments` | `official_form_actor_assignments_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_instances` | `official_form_instances_current_version_id_foreign` | `FOREIGN KEY (current_version_id) REFERENCES official_form_versions(id) ON DELETE SET NULL` |
| `official_form_instances` | `official_form_instances_defense_evaluation_id_foreign` | `FOREIGN KEY (defense_evaluation_id) REFERENCES defense_evaluations(id) ON DELETE RESTRICT` |
| `official_form_instances` | `official_form_instances_initiated_by_foreign` | `FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_instances` | `official_form_instances_official_form_definition_id_foreign` | `FOREIGN KEY (official_form_definition_id) REFERENCES official_form_definitions(id) ON DELETE RESTRICT` |
| `official_form_instances` | `official_form_instances_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE SET NULL` |
| `official_form_instances` | `official_form_instances_research_class_id_foreign` | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE SET NULL` |
| `official_form_signatures` | `official_form_signatures_official_form_instance_id_foreign` | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `official_form_signatures` | `official_form_signatures_official_form_version_id_foreign` | `FOREIGN KEY (official_form_version_id) REFERENCES official_form_versions(id) ON DELETE RESTRICT` |
| `official_form_signatures` | `official_form_signatures_signer_user_id_foreign` | `FOREIGN KEY (signer_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_signatures` | `official_form_signatures_user_signature_id_foreign` | `FOREIGN KEY (user_signature_id) REFERENCES user_signatures(id) ON DELETE SET NULL` |
| `official_form_verifications` | `official_form_verifications_official_form_version_id_foreign` | `FOREIGN KEY (official_form_version_id) REFERENCES official_form_versions(id) ON DELETE RESTRICT` |
| `official_form_versions` | `official_form_versions_created_by_foreign` | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_versions` | `official_form_versions_official_form_instance_id_foreign` | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `official_form_versions` | `official_form_versions_supersedes_version_id_foreign` | `FOREIGN KEY (supersedes_version_id) REFERENCES official_form_versions(id) ON DELETE SET NULL` |
| `programs` | `programs_department_id_foreign` | `FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT` |
| `research_class_actor_assignments` | `research_class_actor_assignments_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_actor_assignments` | `research_class_actor_assignments_research_class_id_foreign` | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_actor_assignments` | `research_class_actor_assignments_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_enrollments` | `research_class_enrollments_research_class_id_foreign` | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_enrollments` | `research_class_enrollments_reviewed_by_foreign` | `FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_enrollments` | `research_class_enrollments_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `research_class_group_adviser_histories` | `research_class_group_adviser_histories_adviser_id_foreign` | `FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_histories` | `research_class_group_adviser_histories_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_histories` | `research_class_group_adviser_histories_ended_by_foreign` | `FOREIGN KEY (ended_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_group_adviser_histories` | `research_class_group_adviser_histories_research_class_group_id_` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `research_class_group_adviser_requests` | `research_class_group_adviser_requests_adviser_id_foreign` | `FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_requests` | `research_class_group_adviser_requests_requested_by_foreign` | `FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_requests` | `research_class_group_adviser_requests_research_class_group_id_f` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `research_class_group_member_histories` | `research_class_group_member_histories_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_group_member_histories` | `research_class_group_member_histories_research_class_enrollment` | `FOREIGN KEY (research_class_enrollment_id) REFERENCES research_class_enrollments(id) ON DELETE SET NULL` |
| `research_class_group_member_histories` | `research_class_group_member_histories_research_class_group_id_f` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `research_class_group_member_histories` | `research_class_group_member_histories_research_class_id_foreign` | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE RESTRICT` |
| `research_class_group_member_histories` | `research_class_group_member_histories_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_members` | `research_class_group_members_assigned_by_foreign` | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_members` | `research_class_group_members_research_class_enrollment_id_forei` | `FOREIGN KEY (research_class_enrollment_id) REFERENCES research_class_enrollments(id) ON DELETE CASCADE` |
| `research_class_group_members` | `research_class_group_members_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `research_class_group_members` | `research_class_group_members_research_class_id_foreign` | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_group_members` | `research_class_group_members_student_id_foreign` | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `research_class_groups` | `research_class_groups_adviser_id_foreign` | `FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_groups` | `research_class_groups_created_by_foreign` | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_groups` | `research_class_groups_leader_student_id_foreign` | `FOREIGN KEY (leader_student_id) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_groups` | `research_class_groups_research_class_id_foreign` | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_groups` | `research_class_groups_research_group_id_foreign` | `FOREIGN KEY (research_group_id) REFERENCES research_groups(id) ON DELETE SET NULL` |
| `research_classes` | `research_classes_adviser_id_foreign` | `FOREIGN KEY (facilitator_id) REFERENCES users(id) ON DELETE CASCADE` |
| `research_group_members` | `research_group_members_research_group_id_foreign` | `FOREIGN KEY (research_group_id) REFERENCES research_groups(id) ON DELETE CASCADE` |
| `research_group_members` | `research_group_members_student_profile_id_foreign` | `FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE` |
| `research_group_milestone_events` | `research_group_milestone_events_actor_id_foreign` | `FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestone_events` | `research_group_milestone_events_research_group_milestone_id_for` | `FOREIGN KEY (research_group_milestone_id) REFERENCES research_group_milestones(id) ON DELETE RESTRICT` |
| `research_group_milestones` | `research_group_milestones_completed_by_foreign` | `FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestones` | `research_group_milestones_milestone_definition_id_foreign` | `FOREIGN KEY (milestone_definition_id) REFERENCES milestone_definitions(id) ON DELETE RESTRICT` |
| `research_group_milestones` | `research_group_milestones_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `research_group_milestones` | `research_group_milestones_started_by_foreign` | `FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestones` | `research_group_milestones_updated_by_foreign` | `FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_groups` | `research_groups_academic_term_id_foreign` | `FOREIGN KEY (academic_term_id) REFERENCES academic_terms(id) ON DELETE RESTRICT` |
| `research_groups` | `research_groups_created_by_foreign` | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_groups` | `research_groups_program_id_foreign` | `FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT` |
| `research_projects` | `research_projects_created_by_foreign` | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_projects` | `research_projects_research_group_id_foreign` | `FOREIGN KEY (research_group_id) REFERENCES research_groups(id) ON DELETE RESTRICT` |
| `research_proposals` | `research_proposals_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `research_proposals` | `research_proposals_reviewed_by_foreign` | `FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_proposals` | `research_proposals_submitted_by_foreign` | `FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE` |
| `revision_request_events` | `revision_request_events_actor_id_foreign` | `FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `revision_request_events` | `revision_request_events_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `revision_request_events` | `revision_request_events_revision_request_id_foreign` | `FOREIGN KEY (revision_request_id) REFERENCES revision_requests(id) ON DELETE CASCADE` |
| `revision_requests` | `revision_requests_assigned_to_foreign` | `FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE` |
| `revision_requests` | `revision_requests_document_id_foreign` | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `revision_requests` | `revision_requests_requested_by_foreign` | `FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `revision_requests` | `revision_requests_research_class_group_id_foreign` | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `revision_requests` | `revision_requests_source_document_review_id_foreign` | `FOREIGN KEY (source_document_review_id) REFERENCES document_reviews(id) ON DELETE SET NULL` |
| `revision_requests` | `revision_requests_submitted_document_id_foreign` | `FOREIGN KEY (submitted_document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `role_has_permissions` | `role_has_permissions_permission_id_foreign` | `FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE` |
| `role_has_permissions` | `role_has_permissions_role_id_foreign` | `FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE` |
| `signature_audits` | `signature_audits_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `signature_audits` | `signature_audits_user_signature_id_foreign` | `FOREIGN KEY (user_signature_id) REFERENCES user_signatures(id) ON DELETE SET NULL` |
| `student_profiles` | `student_profiles_program_id_foreign` | `FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT` |
| `student_profiles` | `student_profiles_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `system_settings` | `system_settings_updated_by_foreign` | `FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL` |
| `title_presentations` | `title_presentations_defense_id_foreign` | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `title_presentations` | `title_presentations_finalized_by_foreign` | `FOREIGN KEY (finalized_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `title_presentations` | `title_presentations_official_form_instance_id_foreign` | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `title_presentations` | `title_presentations_official_form_version_id_foreign` | `FOREIGN KEY (official_form_version_id) REFERENCES official_form_versions(id) ON DELETE RESTRICT` |
| `title_presentations` | `title_presentations_presentation_completed_by_foreign` | `FOREIGN KEY (presentation_completed_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `title_presentations` | `title_presentations_result_recorded_by_foreign` | `FOREIGN KEY (result_recorded_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `user_signatures` | `user_signatures_user_id_foreign` | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |

## Eloquent model-to-table map

This map is obtained by instantiating each concrete class in `app/Models` and reading Eloquent’s runtime table name.

| Eloquent model | Live table |
| --- | --- |
| `App\Models\AcademicTerm` | `academic_terms` |
| `App\Models\AcademicYear` | `academic_years` |
| `App\Models\AuditLog` | `audit_logs` |
| `App\Models\ConsultationAttendance` | `consultation_attendances` |
| `App\Models\ConsultationAudit` | `consultation_audits` |
| `App\Models\ConsultationRecord` | `consultation_records` |
| `App\Models\ConsultationRequest` | `consultation_requests` |
| `App\Models\ConsultationScheduleProposal` | `consultation_schedule_proposals` |
| `App\Models\DefenseEvaluationRoundPanelist` | `defense_evaluation_round_panelists` |
| `App\Models\DefenseEvaluationRoundStudent` | `defense_evaluation_round_students` |
| `App\Models\DefenseEvaluationRound` | `defense_evaluation_rounds` |
| `App\Models\DefenseEvaluationStudentScore` | `defense_evaluation_student_scores` |
| `App\Models\DefenseEvaluationStudentSummary` | `defense_evaluation_student_summaries` |
| `App\Models\DefenseEvaluationSummary` | `defense_evaluation_summaries` |
| `App\Models\DefenseEvaluation` | `defense_evaluations` |
| `App\Models\DefensePanelAssignment` | `defense_panel_assignments` |
| `App\Models\DefenseRoom` | `defense_rooms` |
| `App\Models\DefenseSchedule` | `defense_schedules` |
| `App\Models\Defense` | `defenses` |
| `App\Models\DocumentAccessAudit` | `document_access_audits` |
| `App\Models\DocumentReviewAudit` | `document_review_audits` |
| `App\Models\DocumentReviewComment` | `document_review_comments` |
| `App\Models\DocumentReview` | `document_reviews` |
| `App\Models\DocumentUploadAudit` | `document_upload_audits` |
| `App\Models\Document` | `documents` |
| `App\Models\MilestoneDefinition` | `milestone_definitions` |
| `App\Models\MilestoneEvidence` | `milestone_evidences` |
| `App\Models\OfficialFormActorAssignment` | `official_form_actor_assignments` |
| `App\Models\OfficialFormDefinition` | `official_form_definitions` |
| `App\Models\OfficialFormInstance` | `official_form_instances` |
| `App\Models\OfficialFormSignature` | `official_form_signatures` |
| `App\Models\OfficialFormVerification` | `official_form_verifications` |
| `App\Models\OfficialFormVersion` | `official_form_versions` |
| `App\Models\ResearchClassActorAssignment` | `research_class_actor_assignments` |
| `App\Models\ResearchClassEnrollment` | `research_class_enrollments` |
| `App\Models\ResearchClassGroupAdviserHistory` | `research_class_group_adviser_histories` |
| `App\Models\ResearchClassGroupAdviserRequest` | `research_class_group_adviser_requests` |
| `App\Models\ResearchClassGroupMemberHistory` | `research_class_group_member_histories` |
| `App\Models\ResearchClassGroupMember` | `research_class_group_members` |
| `App\Models\ResearchClassGroup` | `research_class_groups` |
| `App\Models\ResearchClass` | `research_classes` |
| `App\Models\ResearchGroupMilestoneEvent` | `research_group_milestone_events` |
| `App\Models\ResearchGroupMilestone` | `research_group_milestones` |
| `App\Models\ResearchGroup` | `research_groups` |
| `App\Models\ResearchProject` | `research_projects` |
| `App\Models\ResearchProposal` | `research_proposals` |
| `App\Models\RevisionRequestEvent` | `revision_request_events` |
| `App\Models\RevisionRequest` | `revision_requests` |
| `App\Models\SystemSetting` | `system_settings` |
| `App\Models\TitlePresentation` | `title_presentations` |
| `App\Models\UserSignature` | `user_signatures` |
| `App\Models\User` | `users` |

## Complete table definitions

### `academic_terms`

- Current rows: **5**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('academic_terms_id_seq'::regclass)` | — |
| 2 | `academic_year_id` | `bigint` | No | `—` | — |
| 3 | `name` | `character varying(50)` | No | `—` | — |
| 4 | `starts_at` | `date` | No | `—` | — |
| 5 | `ends_at` | `date` | No | `—` | — |
| 6 | `is_current` | `boolean` | No | `false` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `academic_terms_academic_year_id_foreign` | FOREIGN KEY | `FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE` |
| `academic_terms_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `academic_terms_academic_year_id_name_unique` | UNIQUE | `UNIQUE (academic_year_id, name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `academic_terms_academic_year_id_name_unique` | `CREATE UNIQUE INDEX academic_terms_academic_year_id_name_unique ON public.academic_terms USING btree (academic_year_id, name)` |
| `academic_terms_pkey` | `CREATE UNIQUE INDEX academic_terms_pkey ON public.academic_terms USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `academic_years`

- Current rows: **2**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('academic_years_id_seq'::regclass)` | — |
| 2 | `name` | `character varying(30)` | No | `—` | — |
| 3 | `starts_at` | `date` | No | `—` | — |
| 4 | `ends_at` | `date` | No | `—` | — |
| 5 | `is_current` | `boolean` | No | `false` | — |
| 6 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `academic_years_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `academic_years_name_unique` | UNIQUE | `UNIQUE (name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `academic_years_name_unique` | `CREATE UNIQUE INDEX academic_years_name_unique ON public.academic_years USING btree (name)` |
| `academic_years_pkey` | `CREATE UNIQUE INDEX academic_years_pkey ON public.academic_years USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `adviser_assignments`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('adviser_assignments_id_seq'::regclass)` | — |
| 2 | `research_project_id` | `bigint` | No | `—` | — |
| 3 | `adviser_id` | `bigint` | No | `—` | — |
| 4 | `assigned_by` | `bigint` | Yes | `—` | — |
| 5 | `status` | `character varying(30)` | No | `'active'::character varying` | — |
| 6 | `remarks` | `text` | Yes | `—` | — |
| 7 | `assigned_at` | `timestamp(0) with time zone` | No | `CURRENT_TIMESTAMP` | — |
| 8 | `ended_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `adviser_assignments_adviser_id_foreign` | FOREIGN KEY | `FOREIGN KEY (adviser_id) REFERENCES faculty_profiles(id) ON DELETE RESTRICT` |
| `adviser_assignments_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `adviser_assignments_research_project_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_project_id) REFERENCES research_projects(id) ON DELETE CASCADE` |
| `adviser_assignments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `adviser_assignments_adviser_id_status_index` | `CREATE INDEX adviser_assignments_adviser_id_status_index ON public.adviser_assignments USING btree (adviser_id, status)` |
| `adviser_assignments_pkey` | `CREATE UNIQUE INDEX adviser_assignments_pkey ON public.adviser_assignments USING btree (id)` |
| `adviser_assignments_research_project_id_status_index` | `CREATE INDEX adviser_assignments_research_project_id_status_index ON public.adviser_assignments USING btree (research_project_id, status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `audit_logs`

- Current rows: **45**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_10_000001_create_audit_logs_table.php (create)`, `2026_08_10_000001_create_audit_logs_table.php (drop in down/cleanup path)`, `2026_08_10_000002_add_subject_snapshots_to_audit_logs_table.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('audit_logs_id_seq'::regclass)` | — |
| 2 | `user_id` | `bigint` | Yes | `—` | — |
| 3 | `actor_name` | `character varying(255)` | Yes | `—` | — |
| 4 | `actor_email` | `character varying(255)` | Yes | `—` | — |
| 5 | `event` | `character varying(120)` | No | `—` | — |
| 6 | `auditable_type` | `character varying(255)` | Yes | `—` | — |
| 7 | `auditable_id` | `bigint` | Yes | `—` | — |
| 8 | `description` | `text` | Yes | `—` | — |
| 9 | `old_values` | `json` | Yes | `—` | — |
| 10 | `new_values` | `json` | Yes | `—` | — |
| 11 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 12 | `user_agent` | `text` | Yes | `—` | — |
| 13 | `created_at` | `timestamp(0) with time zone` | No | `CURRENT_TIMESTAMP` | — |
| 14 | `subject_name` | `character varying(255)` | Yes | `—` | — |
| 15 | `subject_email` | `character varying(255)` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `audit_logs_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `audit_logs_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `audit_logs_auditable_type_auditable_id_index` | `CREATE INDEX audit_logs_auditable_type_auditable_id_index ON public.audit_logs USING btree (auditable_type, auditable_id)` |
| `audit_logs_event_created_at_index` | `CREATE INDEX audit_logs_event_created_at_index ON public.audit_logs USING btree (event, created_at)` |
| `audit_logs_pkey` | `CREATE UNIQUE INDEX audit_logs_pkey ON public.audit_logs USING btree (id)` |
| `audit_logs_subject_email_index` | `CREATE INDEX audit_logs_subject_email_index ON public.audit_logs USING btree (subject_email)` |
| `audit_logs_user_id_created_at_index` | `CREATE INDEX audit_logs_user_id_created_at_index ON public.audit_logs USING btree (user_id, created_at)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `cache`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000001_create_cache_table.php (create)`, `0001_01_01_000001_create_cache_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `key` | `character varying(255)` | No | `—` | — |
| 2 | `value` | `text` | No | `—` | — |
| 3 | `expiration` | `bigint` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `cache_pkey` | PRIMARY KEY | `PRIMARY KEY (key)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `cache_expiration_index` | `CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration)` |
| `cache_pkey` | `CREATE UNIQUE INDEX cache_pkey ON public.cache USING btree (key)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `cache_locks`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000001_create_cache_table.php (create)`, `0001_01_01_000001_create_cache_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `key` | `character varying(255)` | No | `—` | — |
| 2 | `owner` | `character varying(255)` | No | `—` | — |
| 3 | `expiration` | `bigint` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `cache_locks_pkey` | PRIMARY KEY | `PRIMARY KEY (key)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `cache_locks_expiration_index` | `CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration)` |
| `cache_locks_pkey` | `CREATE UNIQUE INDEX cache_locks_pkey ON public.cache_locks USING btree (key)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `colleges`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('colleges_id_seq'::regclass)` | — |
| 2 | `code` | `character varying(30)` | No | `—` | — |
| 3 | `name` | `character varying(255)` | No | `—` | — |
| 4 | `is_active` | `boolean` | No | `true` | — |
| 5 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 6 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `colleges_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `colleges_code_unique` | UNIQUE | `UNIQUE (code)` |
| `colleges_name_unique` | UNIQUE | `UNIQUE (name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `colleges_code_unique` | `CREATE UNIQUE INDEX colleges_code_unique ON public.colleges USING btree (code)` |
| `colleges_name_unique` | `CREATE UNIQUE INDEX colleges_name_unique ON public.colleges USING btree (name)` |
| `colleges_pkey` | `CREATE UNIQUE INDEX colleges_pkey ON public.colleges USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `consultation_attendances`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_09_000003_create_phase16_consultation_records_tables.php (create)`, `2026_08_09_000003_create_phase16_consultation_records_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('consultation_attendances_id_seq'::regclass)` | — |
| 2 | `consultation_record_id` | `bigint` | No | `—` | — |
| 3 | `student_id` | `bigint` | No | `—` | — |
| 4 | `attended` | `boolean` | No | `true` | — |
| 5 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 6 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `consultation_attendances_consultation_record_id_foreign` | FOREIGN KEY | `FOREIGN KEY (consultation_record_id) REFERENCES consultation_records(id) ON DELETE CASCADE` |
| `consultation_attendances_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_attendances_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `consultation_attendances_consultation_record_id_student_id_uniq` | UNIQUE | `UNIQUE (consultation_record_id, student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `consultation_attendances_consultation_record_id_student_id_uniq` | `CREATE UNIQUE INDEX consultation_attendances_consultation_record_id_student_id_uniq ON public.consultation_attendances USING btree (consultation_record_id, student_id)` |
| `consultation_attendances_pkey` | `CREATE UNIQUE INDEX consultation_attendances_pkey ON public.consultation_attendances USING btree (id)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `consultation_audits`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_09_000003_create_phase16_consultation_records_tables.php (create)`, `2026_08_09_000003_create_phase16_consultation_records_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('consultation_audits_id_seq'::regclass)` | — |
| 2 | `consultation_request_id` | `bigint` | No | `—` | — |
| 3 | `research_class_group_id` | `bigint` | No | `—` | — |
| 4 | `actor_id` | `bigint` | No | `—` | — |
| 5 | `action` | `character varying(50)` | No | `—` | — |
| 6 | `status` | `character varying(20)` | No | `—` | — |
| 7 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 8 | `occurred_at` | `timestamp(0) with time zone` | No | `—` | — |
| 9 | `metadata` | `json` | Yes | `—` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `consultation_audits_actor_id_foreign` | FOREIGN KEY | `FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_audits_consultation_request_id_foreign` | FOREIGN KEY | `FOREIGN KEY (consultation_request_id) REFERENCES consultation_requests(id) ON DELETE CASCADE` |
| `consultation_audits_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `consultation_audits_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `consultation_audits_consultation_request_id_index` | `CREATE INDEX consultation_audits_consultation_request_id_index ON public.consultation_audits USING btree (consultation_request_id)` |
| `consultation_audits_pkey` | `CREATE UNIQUE INDEX consultation_audits_pkey ON public.consultation_audits USING btree (id)` |
| `consultation_audits_research_class_group_id_action_index` | `CREATE INDEX consultation_audits_research_class_group_id_action_index ON public.consultation_audits USING btree (research_class_group_id, action)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `consultation_records`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_09_000003_create_phase16_consultation_records_tables.php (create)`, `2026_08_09_000003_create_phase16_consultation_records_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('consultation_records_id_seq'::regclass)` | — |
| 2 | `consultation_request_id` | `bigint` | No | `—` | — |
| 3 | `research_class_group_id` | `bigint` | No | `—` | — |
| 4 | `conducted_by` | `bigint` | No | `—` | — |
| 5 | `consulted_at` | `timestamp(0) with time zone` | No | `—` | — |
| 6 | `duration_minutes` | `integer` | No | `60` | — |
| 7 | `consultation_mode` | `character varying(20)` | No | `—` | — |
| 8 | `location` | `text` | Yes | `—` | — |
| 9 | `meeting_url` | `text` | Yes | `—` | — |
| 10 | `agenda` | `text` | No | `—` | — |
| 11 | `discussion` | `text` | No | `—` | — |
| 12 | `recommendations` | `text` | Yes | `—` | — |
| 13 | `next_consultation_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 14 | `supersedes_record_id` | `bigint` | Yes | `—` | — |
| 15 | `is_superseded` | `boolean` | No | `false` | — |
| 16 | `correction_reason` | `text` | Yes | `—` | — |
| 17 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 18 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `consultation_records_conducted_by_foreign` | FOREIGN KEY | `FOREIGN KEY (conducted_by) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_records_consultation_request_id_foreign` | FOREIGN KEY | `FOREIGN KEY (consultation_request_id) REFERENCES consultation_requests(id) ON DELETE CASCADE` |
| `consultation_records_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `consultation_records_supersedes_record_id_foreign` | FOREIGN KEY | `FOREIGN KEY (supersedes_record_id) REFERENCES consultation_records(id) ON DELETE SET NULL` |
| `consultation_records_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `consultation_records_conducted_by_index` | `CREATE INDEX consultation_records_conducted_by_index ON public.consultation_records USING btree (conducted_by)` |
| `consultation_records_consultation_request_id_index` | `CREATE INDEX consultation_records_consultation_request_id_index ON public.consultation_records USING btree (consultation_request_id)` |
| `consultation_records_pkey` | `CREATE UNIQUE INDEX consultation_records_pkey ON public.consultation_records USING btree (id)` |
| `consultation_records_research_class_group_id_index` | `CREATE INDEX consultation_records_research_class_group_id_index ON public.consultation_records USING btree (research_class_group_id)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `consultation_requests`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000003_create_consultation_requests_table.php (create)`, `2026_07_24_000003_create_consultation_requests_table.php (drop in down/cleanup path)`, `2026_08_09_000003_create_phase16_consultation_records_tables.php (alter)`, `2026_08_09_000003_create_phase16_consultation_records_tables.php (create)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('consultation_requests_id_seq'::regclass)` | — |
| 2 | `research_project_id` | `bigint` | Yes | `—` | — |
| 3 | `adviser_assignment_id` | `bigint` | Yes | `—` | — |
| 4 | `requested_by` | `bigint` | No | `—` | — |
| 5 | `request_token` | `uuid` | No | `—` | — |
| 6 | `preferred_at` | `timestamp(0) with time zone` | No | `—` | — |
| 7 | `consultation_mode` | `character varying(20)` | No | `—` | — |
| 8 | `agenda` | `text` | No | `—` | — |
| 9 | `status` | `character varying(20)` | No | `'pending'::character varying` | — |
| 10 | `reviewed_by` | `bigint` | Yes | `—` | — |
| 11 | `reviewed_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 12 | `review_notes` | `text` | Yes | `—` | — |
| 13 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 14 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 15 | `research_class_group_id` | `bigint` | Yes | `—` | — |
| 16 | `assigned_adviser_id` | `bigint` | Yes | `—` | — |
| 17 | `confirmed_start_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 18 | `confirmed_end_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 19 | `duration_minutes` | `integer` | No | `60` | — |
| 20 | `location` | `text` | Yes | `—` | — |
| 21 | `meeting_url` | `text` | Yes | `—` | — |
| 22 | `document_stage` | `character varying(40)` | Yes | `—` | — |
| 23 | `document_id` | `bigint` | Yes | `—` | — |
| 24 | `cancelled_by` | `bigint` | Yes | `—` | — |
| 25 | `cancelled_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 26 | `cancellation_reason` | `text` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `consultation_requests_adviser_assignment_id_foreign` | FOREIGN KEY | `FOREIGN KEY (adviser_assignment_id) REFERENCES adviser_assignments(id) ON DELETE CASCADE` |
| `consultation_requests_assigned_adviser_id_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_adviser_id) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_requests_cancelled_by_foreign` | FOREIGN KEY | `FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_requests_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `consultation_requests_requested_by_foreign` | FOREIGN KEY | `FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_requests_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE SET NULL` |
| `consultation_requests_research_project_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_project_id) REFERENCES research_projects(id) ON DELETE CASCADE` |
| `consultation_requests_reviewed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_requests_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `consultation_requests_requested_by_request_token_unique` | UNIQUE | `UNIQUE (requested_by, request_token)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `consultation_requests_adviser_assignment_id_status_index` | `CREATE INDEX consultation_requests_adviser_assignment_id_status_index ON public.consultation_requests USING btree (adviser_assignment_id, status)` |
| `consultation_requests_pkey` | `CREATE UNIQUE INDEX consultation_requests_pkey ON public.consultation_requests USING btree (id)` |
| `consultation_requests_requested_by_request_token_unique` | `CREATE UNIQUE INDEX consultation_requests_requested_by_request_token_unique ON public.consultation_requests USING btree (requested_by, request_token)` |
| `consultation_requests_research_project_id_status_preferred_at_i` | `CREATE INDEX consultation_requests_research_project_id_status_preferred_at_i ON public.consultation_requests USING btree (research_project_id, status, preferred_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `consultation_schedule_proposals`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_09_000003_create_phase16_consultation_records_tables.php (create)`, `2026_08_09_000003_create_phase16_consultation_records_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('consultation_schedule_proposals_id_seq'::regclass)` | — |
| 2 | `consultation_request_id` | `bigint` | No | `—` | — |
| 3 | `proposed_by` | `bigint` | No | `—` | — |
| 4 | `proposed_start_at` | `timestamp(0) with time zone` | No | `—` | — |
| 5 | `duration_minutes` | `integer` | No | `60` | — |
| 6 | `reason` | `text` | Yes | `—` | — |
| 7 | `status` | `character varying(20)` | No | `'pending_response'::character varying` | — |
| 8 | `responded_by` | `bigint` | Yes | `—` | — |
| 9 | `responded_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `consultation_schedule_proposals_consultation_request_id_foreign` | FOREIGN KEY | `FOREIGN KEY (consultation_request_id) REFERENCES consultation_requests(id) ON DELETE CASCADE` |
| `consultation_schedule_proposals_proposed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (proposed_by) REFERENCES users(id) ON DELETE CASCADE` |
| `consultation_schedule_proposals_responded_by_foreign` | FOREIGN KEY | `FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL` |
| `consultation_schedule_proposals_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `consultation_schedule_proposals_consultation_request_id_status_` | `CREATE INDEX consultation_schedule_proposals_consultation_request_id_status_ ON public.consultation_schedule_proposals USING btree (consultation_request_id, status)` |
| `consultation_schedule_proposals_pkey` | `CREATE UNIQUE INDEX consultation_schedule_proposals_pkey ON public.consultation_schedule_proposals USING btree (id)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `defense_evaluation_round_panelists`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluation_round_panelists_id_seq'::regclass)` | — |
| 2 | `defense_evaluation_round_id` | `bigint` | No | `—` | — |
| 3 | `defense_panel_assignment_id` | `bigint` | No | `—` | — |
| 4 | `panelist_user_id` | `bigint` | No | `—` | — |
| 5 | `position` | `smallint` | No | `—` | — |
| 6 | `created_at` | `timestamp(0) without time zone` | No | `CURRENT_TIMESTAMP` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluation_round_panelists_defense_evaluation_round_id_` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE CASCADE` |
| `defense_evaluation_round_panelists_defense_panel_assignment_id_` | FOREIGN KEY | `FOREIGN KEY (defense_panel_assignment_id) REFERENCES defense_panel_assignments(id) ON DELETE RESTRICT` |
| `defense_evaluation_round_panelists_panelist_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (panelist_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_round_panelists_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `derp_round_panelist_unique` | UNIQUE | `UNIQUE (defense_evaluation_round_id, panelist_user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_evaluation_round_panelists_pkey` | `CREATE UNIQUE INDEX defense_evaluation_round_panelists_pkey ON public.defense_evaluation_round_panelists USING btree (id)` |
| `derp_round_panelist_unique` | `CREATE UNIQUE INDEX derp_round_panelist_unique ON public.defense_evaluation_round_panelists USING btree (defense_evaluation_round_id, panelist_user_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_evaluation_round_students`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluation_round_students_id_seq'::regclass)` | — |
| 2 | `defense_evaluation_round_id` | `bigint` | No | `—` | — |
| 3 | `student_id` | `bigint` | No | `—` | — |
| 4 | `student_name_snapshot` | `character varying(255)` | No | `—` | — |
| 5 | `group_member_id` | `bigint` | Yes | `—` | — |
| 6 | `created_at` | `timestamp(0) without time zone` | No | `CURRENT_TIMESTAMP` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluation_round_students_defense_evaluation_round_id_f` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE CASCADE` |
| `defense_evaluation_round_students_group_member_id_foreign` | FOREIGN KEY | `FOREIGN KEY (group_member_id) REFERENCES research_class_group_members(id) ON DELETE SET NULL` |
| `defense_evaluation_round_students_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_round_students_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `ders_round_student_unique` | UNIQUE | `UNIQUE (defense_evaluation_round_id, student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_evaluation_round_students_pkey` | `CREATE UNIQUE INDEX defense_evaluation_round_students_pkey ON public.defense_evaluation_round_students USING btree (id)` |
| `ders_round_student_unique` | `CREATE UNIQUE INDEX ders_round_student_unique ON public.defense_evaluation_round_students USING btree (defense_evaluation_round_id, student_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_evaluation_rounds`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluation_rounds_id_seq'::regclass)` | — |
| 2 | `defense_id` | `bigint` | No | `—` | — |
| 3 | `defense_schedule_id` | `bigint` | No | `—` | — |
| 4 | `research_class_group_id` | `bigint` | No | `—` | — |
| 5 | `status` | `character varying(255)` | No | `'open'::character varying` | — |
| 6 | `summary_signer_user_id` | `bigint` | Yes | `—` | — |
| 7 | `opened_by` | `bigint` | No | `—` | — |
| 8 | `opened_at` | `timestamp(0) without time zone` | No | `—` | — |
| 9 | `all_submitted_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 10 | `finalized_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 11 | `released_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 12 | `released_by` | `bigint` | Yes | `—` | — |
| 13 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 14 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluation_rounds_defense_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds_defense_schedule_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_schedule_id) REFERENCES defense_schedules(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds_opened_by_foreign` | FOREIGN KEY | `FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds_released_by_foreign` | FOREIGN KEY | `FOREIGN KEY (released_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds_summary_signer_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (summary_signer_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_rounds_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_evaluation_rounds_defense_id_status_index` | `CREATE INDEX defense_evaluation_rounds_defense_id_status_index ON public.defense_evaluation_rounds USING btree (defense_id, status)` |
| `defense_evaluation_rounds_defense_schedule_id_index` | `CREATE INDEX defense_evaluation_rounds_defense_schedule_id_index ON public.defense_evaluation_rounds USING btree (defense_schedule_id)` |
| `defense_evaluation_rounds_pkey` | `CREATE UNIQUE INDEX defense_evaluation_rounds_pkey ON public.defense_evaluation_rounds USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_evaluation_student_scores`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluation_student_scores_id_seq'::regclass)` | — |
| 2 | `defense_evaluation_id` | `bigint` | No | `—` | — |
| 3 | `round_student_id` | `bigint` | No | `—` | — |
| 4 | `student_id` | `bigint` | No | `—` | — |
| 5 | `communication_score` | `numeric(5,2)` | Yes | `—` | — |
| 6 | `organization_score` | `numeric(5,2)` | Yes | `—` | — |
| 7 | `effectiveness_score` | `numeric(5,2)` | Yes | `—` | — |
| 8 | `presentation_total` | `numeric(5,2)` | Yes | `—` | — |
| 9 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 10 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluation_student_scores_defense_evaluation_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_id) REFERENCES defense_evaluations(id) ON DELETE CASCADE` |
| `defense_evaluation_student_scores_round_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (round_student_id) REFERENCES defense_evaluation_round_students(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_scores_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_scores_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `dess_eval_student_unique` | UNIQUE | `UNIQUE (defense_evaluation_id, student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_evaluation_student_scores_pkey` | `CREATE UNIQUE INDEX defense_evaluation_student_scores_pkey ON public.defense_evaluation_student_scores USING btree (id)` |
| `dess_eval_student_unique` | `CREATE UNIQUE INDEX dess_eval_student_unique ON public.defense_evaluation_student_scores USING btree (defense_evaluation_id, student_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_evaluation_student_summaries`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluation_student_summaries_id_seq'::regclass)` | — |
| 2 | `defense_evaluation_summary_id` | `bigint` | No | `—` | — |
| 3 | `round_student_id` | `bigint` | No | `—` | — |
| 4 | `student_id` | `bigint` | No | `—` | — |
| 5 | `presentation_average` | `numeric(5,2)` | No | `—` | — |
| 6 | `created_at` | `timestamp(0) without time zone` | No | `CURRENT_TIMESTAMP` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluation_student_summaries_defense_evaluation_summary` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_summary_id) REFERENCES defense_evaluation_summaries(id) ON DELETE CASCADE` |
| `defense_evaluation_student_summaries_round_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (round_student_id) REFERENCES defense_evaluation_round_students(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_summaries_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluation_student_summaries_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `dessum_summary_student_unique` | UNIQUE | `UNIQUE (defense_evaluation_summary_id, student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_evaluation_student_summaries_pkey` | `CREATE UNIQUE INDEX defense_evaluation_student_summaries_pkey ON public.defense_evaluation_student_summaries USING btree (id)` |
| `dessum_summary_student_unique` | `CREATE UNIQUE INDEX dessum_summary_student_unique ON public.defense_evaluation_student_summaries USING btree (defense_evaluation_summary_id, student_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_evaluation_summaries`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluation_summaries_id_seq'::regclass)` | — |
| 2 | `defense_evaluation_round_id` | `bigint` | No | `—` | — |
| 3 | `research_paper_average` | `numeric(5,2)` | No | `—` | — |
| 4 | `status` | `character varying(255)` | No | `'calculated'::character varying` | — |
| 5 | `finalized_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 6 | `signed_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 7 | `released_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 8 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluation_summaries_defense_evaluation_round_id_foreig` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE RESTRICT` |
| `defense_evaluation_summaries_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `des_round_unique` | UNIQUE | `UNIQUE (defense_evaluation_round_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_evaluation_summaries_pkey` | `CREATE UNIQUE INDEX defense_evaluation_summaries_pkey ON public.defense_evaluation_summaries USING btree (id)` |
| `des_round_unique` | `CREATE UNIQUE INDEX des_round_unique ON public.defense_evaluation_summaries USING btree (defense_evaluation_round_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_evaluations`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_16_000001_create_defense_evaluation_tables.php (create)`, `2026_08_16_000001_create_defense_evaluation_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_evaluations_id_seq'::regclass)` | — |
| 2 | `defense_evaluation_round_id` | `bigint` | No | `—` | — |
| 3 | `round_panelist_id` | `bigint` | No | `—` | — |
| 4 | `panelist_user_id` | `bigint` | No | `—` | — |
| 5 | `status` | `character varying(255)` | No | `'draft'::character varying` | — |
| 6 | `research_quality_score` | `numeric(5,2)` | Yes | `—` | — |
| 7 | `originality_score` | `numeric(5,2)` | Yes | `—` | — |
| 8 | `relevance_score` | `numeric(5,2)` | Yes | `—` | — |
| 9 | `research_paper_total` | `numeric(5,2)` | Yes | `—` | — |
| 10 | `general_comments` | `text` | Yes | `—` | — |
| 11 | `recommendations` | `text` | Yes | `—` | — |
| 12 | `submitted_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 13 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 14 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_evaluations_defense_evaluation_round_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_round_id) REFERENCES defense_evaluation_rounds(id) ON DELETE RESTRICT` |
| `defense_evaluations_panelist_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (panelist_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_evaluations_round_panelist_id_foreign` | FOREIGN KEY | `FOREIGN KEY (round_panelist_id) REFERENCES defense_evaluation_round_panelists(id) ON DELETE RESTRICT` |
| `defense_evaluations_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `de_round_panelist_unique` | UNIQUE | `UNIQUE (defense_evaluation_round_id, panelist_user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `de_round_panelist_unique` | `CREATE UNIQUE INDEX de_round_panelist_unique ON public.defense_evaluations USING btree (defense_evaluation_round_id, panelist_user_id)` |
| `defense_evaluations_pkey` | `CREATE UNIQUE INDEX defense_evaluations_pkey ON public.defense_evaluations USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_panel_assignments`

- Current rows: **6**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_15_000005_create_defense_panel_assignments_table.php (create)`, `2026_08_15_000005_create_defense_panel_assignments_table.php (drop in down/cleanup path)`, `2026_08_22_000001_create_title_presentation_workflow.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_panel_assignments_id_seq'::regclass)` | — |
| 2 | `defense_id` | `bigint` | No | `—` | — |
| 3 | `user_id` | `bigint` | No | `—` | — |
| 4 | `assigned_by` | `bigint` | No | `—` | — |
| 5 | `assigned_at` | `timestamp(0) without time zone` | No | `—` | — |
| 6 | `ended_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 7 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 9 | `panel_position` | `character varying(32)` | Yes | `—` | — |
| 10 | `change_reason` | `text` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_panel_assignments_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_panel_assignments_defense_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `defense_panel_assignments_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_panel_assignments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_panel_assignments_defense_id_user_id_index` | `CREATE INDEX defense_panel_assignments_defense_id_user_id_index ON public.defense_panel_assignments USING btree (defense_id, user_id)` |
| `defense_panel_assignments_pkey` | `CREATE UNIQUE INDEX defense_panel_assignments_pkey ON public.defense_panel_assignments USING btree (id)` |
| `defense_panel_assignments_user_id_ended_at_index` | `CREATE INDEX defense_panel_assignments_user_id_ended_at_index ON public.defense_panel_assignments USING btree (user_id, ended_at)` |
| `defense_panel_position_active_idx` | `CREATE INDEX defense_panel_position_active_idx ON public.defense_panel_assignments USING btree (defense_id, panel_position, ended_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_rooms`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_15_000001_create_defense_rooms_table.php (create)`, `2026_08_15_000001_create_defense_rooms_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_rooms_id_seq'::regclass)` | — |
| 2 | `code` | `character varying(255)` | No | `—` | — |
| 3 | `name` | `character varying(255)` | No | `—` | — |
| 4 | `location_notes` | `text` | Yes | `—` | — |
| 5 | `is_active` | `boolean` | No | `true` | — |
| 6 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 7 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_rooms_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `defense_rooms_code_unique` | UNIQUE | `UNIQUE (code)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_rooms_code_unique` | `CREATE UNIQUE INDEX defense_rooms_code_unique ON public.defense_rooms USING btree (code)` |
| `defense_rooms_is_active_index` | `CREATE INDEX defense_rooms_is_active_index ON public.defense_rooms USING btree (is_active)` |
| `defense_rooms_pkey` | `CREATE UNIQUE INDEX defense_rooms_pkey ON public.defense_rooms USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defense_schedules`

- Current rows: **2**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_15_000003_create_defense_schedules_table.php (create)`, `2026_08_15_000003_create_defense_schedules_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defense_schedules_id_seq'::regclass)` | — |
| 2 | `defense_id` | `bigint` | No | `—` | — |
| 3 | `room_id` | `bigint` | No | `—` | — |
| 4 | `starts_at` | `timestamp(0) without time zone` | No | `—` | — |
| 5 | `ends_at` | `timestamp(0) without time zone` | No | `—` | — |
| 6 | `status` | `character varying(255)` | No | `'current'::character varying` | — |
| 7 | `scheduled_by` | `bigint` | No | `—` | — |
| 8 | `reason` | `text` | Yes | `—` | — |
| 9 | `supersedes_schedule_id` | `bigint` | Yes | `—` | — |
| 10 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defense_schedules_defense_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `defense_schedules_room_id_foreign` | FOREIGN KEY | `FOREIGN KEY (room_id) REFERENCES defense_rooms(id) ON DELETE RESTRICT` |
| `defense_schedules_scheduled_by_foreign` | FOREIGN KEY | `FOREIGN KEY (scheduled_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defense_schedules_supersedes_schedule_id_foreign` | FOREIGN KEY | `FOREIGN KEY (supersedes_schedule_id) REFERENCES defense_schedules(id) ON DELETE RESTRICT` |
| `defense_schedules_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defense_schedules_defense_id_status_index` | `CREATE INDEX defense_schedules_defense_id_status_index ON public.defense_schedules USING btree (defense_id, status)` |
| `defense_schedules_pkey` | `CREATE UNIQUE INDEX defense_schedules_pkey ON public.defense_schedules USING btree (id)` |
| `defense_schedules_room_id_status_index` | `CREATE INDEX defense_schedules_room_id_status_index ON public.defense_schedules USING btree (room_id, status)` |
| `defense_schedules_starts_at_ends_at_index` | `CREATE INDEX defense_schedules_starts_at_ends_at_index ON public.defense_schedules USING btree (starts_at, ends_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `defenses`

- Current rows: **2**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_15_000002_create_defenses_table.php (create)`, `2026_08_15_000002_create_defenses_table.php (drop in down/cleanup path)`, `2026_08_15_000004_add_current_schedule_id_to_defenses_table.php (alter)`, `2026_08_16_000002_add_completed_fields_to_defenses_table.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('defenses_id_seq'::regclass)` | — |
| 2 | `research_class_group_id` | `bigint` | No | `—` | — |
| 3 | `defense_type` | `character varying(255)` | No | `—` | — |
| 4 | `status` | `character varying(255)` | No | `'scheduled'::character varying` | — |
| 5 | `created_by` | `bigint` | No | `—` | — |
| 6 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 7 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 8 | `current_schedule_id` | `bigint` | Yes | `—` | — |
| 9 | `completed_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 10 | `completed_by` | `bigint` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `defenses_completed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `defenses_created_by_foreign` | FOREIGN KEY | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `defenses_current_schedule_id_foreign` | FOREIGN KEY | `FOREIGN KEY (current_schedule_id) REFERENCES defense_schedules(id) ON DELETE SET NULL` |
| `defenses_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `defenses_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `defenses_pkey` | `CREATE UNIQUE INDEX defenses_pkey ON public.defenses USING btree (id)` |
| `defenses_research_class_group_id_defense_type_index` | `CREATE INDEX defenses_research_class_group_id_defense_type_index ON public.defenses USING btree (research_class_group_id, defense_type)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `departments`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('departments_id_seq'::regclass)` | — |
| 2 | `college_id` | `bigint` | No | `—` | — |
| 3 | `code` | `character varying(30)` | No | `—` | — |
| 4 | `name` | `character varying(255)` | No | `—` | — |
| 5 | `is_active` | `boolean` | No | `true` | — |
| 6 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `departments_college_id_foreign` | FOREIGN KEY | `FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE RESTRICT` |
| `departments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `departments_code_unique` | UNIQUE | `UNIQUE (code)` |
| `departments_college_id_name_unique` | UNIQUE | `UNIQUE (college_id, name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `departments_code_unique` | `CREATE UNIQUE INDEX departments_code_unique ON public.departments USING btree (code)` |
| `departments_college_id_name_unique` | `CREATE UNIQUE INDEX departments_college_id_name_unique ON public.departments USING btree (college_id, name)` |
| `departments_pkey` | `CREATE UNIQUE INDEX departments_pkey ON public.departments USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `document_access_audits`

- Current rows: **14**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_09_000001_create_phase14_repository_foundation.php (create)`, `2026_08_09_000001_create_phase14_repository_foundation.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('document_access_audits_id_seq'::regclass)` | — |
| 2 | `document_id` | `bigint` | No | `—` | — |
| 3 | `user_id` | `bigint` | Yes | `—` | — |
| 4 | `action` | `character varying(16)` | No | `—` | — |
| 5 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 6 | `user_agent` | `character varying(1000)` | Yes | `—` | — |
| 7 | `accessed_at` | `timestamp(0) with time zone` | No | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `document_access_audits_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE RESTRICT` |
| `document_access_audits_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `document_access_audits_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `document_access_audits_action_accessed_at_index` | `CREATE INDEX document_access_audits_action_accessed_at_index ON public.document_access_audits USING btree (action, accessed_at)` |
| `document_access_audits_document_id_accessed_at_index` | `CREATE INDEX document_access_audits_document_id_accessed_at_index ON public.document_access_audits USING btree (document_id, accessed_at)` |
| `document_access_audits_pkey` | `CREATE UNIQUE INDEX document_access_audits_pkey ON public.document_access_audits USING btree (id)` |
| `document_access_audits_user_id_accessed_at_index` | `CREATE INDEX document_access_audits_user_id_accessed_at_index ON public.document_access_audits USING btree (user_id, accessed_at)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `document_review_audits`

- Current rows: **4**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_26_000001_create_document_review_integration_tables.php (create)`, `2026_07_26_000001_create_document_review_integration_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('document_review_audits_id_seq'::regclass)` | — |
| 2 | `document_id` | `bigint` | No | `—` | — |
| 3 | `reviewer_id` | `bigint` | No | `—` | — |
| 4 | `student_id` | `bigint` | No | `—` | — |
| 5 | `action` | `character varying(40)` | No | `—` | — |
| 6 | `decision` | `character varying(32)` | Yes | `—` | — |
| 7 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 8 | `occurred_at` | `timestamp(0) with time zone` | No | `—` | — |
| 9 | `metadata` | `json` | Yes | `—` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `document_review_audits_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE` |
| `document_review_audits_reviewer_id_foreign` | FOREIGN KEY | `FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `document_review_audits_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `document_review_audits_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `document_review_audits_action_decision_index` | `CREATE INDEX document_review_audits_action_decision_index ON public.document_review_audits USING btree (action, decision)` |
| `document_review_audits_document_id_occurred_at_index` | `CREATE INDEX document_review_audits_document_id_occurred_at_index ON public.document_review_audits USING btree (document_id, occurred_at)` |
| `document_review_audits_pkey` | `CREATE UNIQUE INDEX document_review_audits_pkey ON public.document_review_audits USING btree (id)` |
| `document_review_audits_reviewer_id_occurred_at_index` | `CREATE INDEX document_review_audits_reviewer_id_occurred_at_index ON public.document_review_audits USING btree (reviewer_id, occurred_at)` |
| `document_review_audits_student_id_occurred_at_index` | `CREATE INDEX document_review_audits_student_id_occurred_at_index ON public.document_review_audits USING btree (student_id, occurred_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `document_review_comments`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_25_000004_create_document_review_tables.php (create)`, `2026_07_25_000004_create_document_review_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('document_review_comments_id_seq'::regclass)` | — |
| 2 | `document_id` | `bigint` | No | `—` | — |
| 3 | `author_id` | `bigint` | No | `—` | — |
| 4 | `parent_id` | `bigint` | Yes | `—` | — |
| 5 | `page_number` | `integer` | Yes | `—` | — |
| 6 | `severity` | `character varying(20)` | No | `'comment'::character varying` | — |
| 7 | `comment` | `text` | No | `—` | — |
| 8 | `resolved_by` | `bigint` | Yes | `—` | — |
| 9 | `resolved_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `document_review_comments_author_id_foreign` | FOREIGN KEY | `FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `document_review_comments_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE` |
| `document_review_comments_parent_id_foreign` | FOREIGN KEY | `FOREIGN KEY (parent_id) REFERENCES document_review_comments(id) ON DELETE CASCADE` |
| `document_review_comments_resolved_by_foreign` | FOREIGN KEY | `FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL` |
| `document_review_comments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `document_review_comments_author_id_created_at_index` | `CREATE INDEX document_review_comments_author_id_created_at_index ON public.document_review_comments USING btree (author_id, created_at)` |
| `document_review_comments_document_id_resolved_at_created_at_ind` | `CREATE INDEX document_review_comments_document_id_resolved_at_created_at_ind ON public.document_review_comments USING btree (document_id, resolved_at, created_at)` |
| `document_review_comments_pkey` | `CREATE UNIQUE INDEX document_review_comments_pkey ON public.document_review_comments USING btree (id)` |
| `document_review_comments_severity_resolved_at_index` | `CREATE INDEX document_review_comments_severity_resolved_at_index ON public.document_review_comments USING btree (severity, resolved_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `document_reviews`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_25_000004_create_document_review_tables.php (create)`, `2026_07_25_000004_create_document_review_tables.php (drop in down/cleanup path)`, `2026_08_09_000002_add_correction_fields_to_document_reviews_table.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('document_reviews_id_seq'::regclass)` | — |
| 2 | `document_id` | `bigint` | No | `—` | — |
| 3 | `reviewer_id` | `bigint` | No | `—` | — |
| 4 | `decision` | `character varying(32)` | No | `—` | — |
| 5 | `review_notes` | `text` | Yes | `—` | — |
| 6 | `reviewed_at` | `timestamp(0) with time zone` | No | `—` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `supersedes_review_id` | `bigint` | Yes | `—` | — |
| 10 | `is_superseded` | `boolean` | No | `false` | — |
| 11 | `correction_reason` | `text` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `document_reviews_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE` |
| `document_reviews_reviewer_id_foreign` | FOREIGN KEY | `FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `document_reviews_supersedes_review_id_foreign` | FOREIGN KEY | `FOREIGN KEY (supersedes_review_id) REFERENCES document_reviews(id) ON DELETE SET NULL` |
| `document_reviews_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `document_reviews_decision_reviewed_at_index` | `CREATE INDEX document_reviews_decision_reviewed_at_index ON public.document_reviews USING btree (decision, reviewed_at)` |
| `document_reviews_document_id_is_superseded_reviewed_at_index` | `CREATE INDEX document_reviews_document_id_is_superseded_reviewed_at_index ON public.document_reviews USING btree (document_id, is_superseded, reviewed_at)` |
| `document_reviews_document_id_reviewed_at_index` | `CREATE INDEX document_reviews_document_id_reviewed_at_index ON public.document_reviews USING btree (document_id, reviewed_at)` |
| `document_reviews_pkey` | `CREATE UNIQUE INDEX document_reviews_pkey ON public.document_reviews USING btree (id)` |
| `document_reviews_reviewer_id_reviewed_at_index` | `CREATE INDEX document_reviews_reviewer_id_reviewed_at_index ON public.document_reviews USING btree (reviewer_id, reviewed_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `document_upload_audits`

- Current rows: **4**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000002_create_document_upload_audits_table.php (create)`, `2026_07_24_000002_create_document_upload_audits_table.php (drop in down/cleanup path)`, `2026_08_08_000002_add_group_leader_and_document_ownership_fields.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('document_upload_audits_id_seq'::regclass)` | — |
| 2 | `document_id` | `bigint` | Yes | `—` | — |
| 3 | `user_id` | `bigint` | Yes | `—` | — |
| 4 | `original_filename` | `character varying(255)` | Yes | `—` | — |
| 5 | `ip_address` | `character varying(45)` | No | `—` | — |
| 6 | `attempted_at` | `timestamp(0) with time zone` | No | `—` | — |
| 7 | `upload_status` | `character varying(16)` | No | `—` | — |
| 8 | `failure_reason` | `character varying(500)` | Yes | `—` | — |
| 9 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `research_class_group_id` | `bigint` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `document_upload_audits_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `document_upload_audits_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE SET NULL` |
| `document_upload_audits_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `document_upload_audits_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `document_upload_audits_pkey` | `CREATE UNIQUE INDEX document_upload_audits_pkey ON public.document_upload_audits USING btree (id)` |
| `document_upload_audits_research_class_group_id_attempted_at_ind` | `CREATE INDEX document_upload_audits_research_class_group_id_attempted_at_ind ON public.document_upload_audits USING btree (research_class_group_id, attempted_at)` |
| `document_upload_audits_upload_status_attempted_at_index` | `CREATE INDEX document_upload_audits_upload_status_attempted_at_index ON public.document_upload_audits USING btree (upload_status, attempted_at)` |
| `document_upload_audits_user_id_attempted_at_index` | `CREATE INDEX document_upload_audits_user_id_attempted_at_index ON public.document_upload_audits USING btree (user_id, attempted_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `documents`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000001_create_documents_table.php (create)`, `2026_07_24_000001_create_documents_table.php (drop in down/cleanup path)`, `2026_07_27_000001_add_revision_workflow_tables.php (alter)`, `2026_08_08_000002_add_group_leader_and_document_ownership_fields.php (alter)`, `2026_08_09_000001_create_phase14_repository_foundation.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('documents_id_seq'::regclass)` | — |
| 2 | `user_id` | `bigint` | No | `—` | — |
| 3 | `submission_token` | `uuid` | No | `—` | — |
| 4 | `original_filename` | `character varying(255)` | No | `—` | — |
| 5 | `stored_filename` | `character varying(255)` | No | `—` | — |
| 6 | `file_type` | `character varying(10)` | No | `—` | — |
| 7 | `mime_type` | `character varying(150)` | No | `—` | — |
| 8 | `file_size` | `bigint` | No | `—` | — |
| 9 | `storage_disk` | `character varying(50)` | No | `—` | — |
| 10 | `storage_path` | `text` | No | `—` | — |
| 11 | `content_sha256` | `character(64)` | No | `—` | — |
| 12 | `submitted_at` | `timestamp(0) with time zone` | No | `—` | — |
| 13 | `status` | `character varying(32)` | No | `'pending'::character varying` | — |
| 14 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 15 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 16 | `revision_request_id` | `bigint` | Yes | `—` | — |
| 17 | `research_class_group_id` | `bigint` | Yes | `—` | — |
| 18 | `version_number` | `integer` | No | `1` | — |
| 19 | `is_current` | `boolean` | No | `true` | — |
| 20 | `document_stage` | `character varying(32)` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `documents_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `documents_revision_request_id_foreign` | FOREIGN KEY | `FOREIGN KEY (revision_request_id) REFERENCES revision_requests(id) ON DELETE SET NULL` |
| `documents_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `documents_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `documents_storage_path_unique` | UNIQUE | `UNIQUE (storage_path)` |
| `documents_user_id_submission_token_unique` | UNIQUE | `UNIQUE (user_id, submission_token)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `documents_content_sha256_index` | `CREATE INDEX documents_content_sha256_index ON public.documents USING btree (content_sha256)` |
| `documents_pkey` | `CREATE UNIQUE INDEX documents_pkey ON public.documents USING btree (id)` |
| `documents_repository_stream_index` | `CREATE INDEX documents_repository_stream_index ON public.documents USING btree (research_class_group_id, document_stage, is_current, submitted_at)` |
| `documents_research_class_group_id_is_current_index` | `CREATE INDEX documents_research_class_group_id_is_current_index ON public.documents USING btree (research_class_group_id, is_current)` |
| `documents_revision_request_id_submitted_at_index` | `CREATE INDEX documents_revision_request_id_submitted_at_index ON public.documents USING btree (revision_request_id, submitted_at)` |
| `documents_status_submitted_at_index` | `CREATE INDEX documents_status_submitted_at_index ON public.documents USING btree (status, submitted_at)` |
| `documents_storage_path_unique` | `CREATE UNIQUE INDEX documents_storage_path_unique ON public.documents USING btree (storage_path)` |
| `documents_user_id_submission_token_unique` | `CREATE UNIQUE INDEX documents_user_id_submission_token_unique ON public.documents USING btree (user_id, submission_token)` |
| `documents_user_id_submitted_at_index` | `CREATE INDEX documents_user_id_submitted_at_index ON public.documents USING btree (user_id, submitted_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `faculty_profiles`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('faculty_profiles_id_seq'::regclass)` | — |
| 2 | `user_id` | `bigint` | No | `—` | — |
| 3 | `department_id` | `bigint` | No | `—` | — |
| 4 | `employee_number` | `character varying(50)` | No | `—` | — |
| 5 | `academic_rank` | `character varying(100)` | Yes | `—` | — |
| 6 | `specialization` | `text` | Yes | `—` | — |
| 7 | `contact_number` | `character varying(30)` | Yes | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `faculty_profiles_department_id_foreign` | FOREIGN KEY | `FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT` |
| `faculty_profiles_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `faculty_profiles_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `faculty_profiles_employee_number_unique` | UNIQUE | `UNIQUE (employee_number)` |
| `faculty_profiles_user_id_unique` | UNIQUE | `UNIQUE (user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `faculty_profiles_employee_number_unique` | `CREATE UNIQUE INDEX faculty_profiles_employee_number_unique ON public.faculty_profiles USING btree (employee_number)` |
| `faculty_profiles_pkey` | `CREATE UNIQUE INDEX faculty_profiles_pkey ON public.faculty_profiles USING btree (id)` |
| `faculty_profiles_user_id_unique` | `CREATE UNIQUE INDEX faculty_profiles_user_id_unique ON public.faculty_profiles USING btree (user_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `failed_jobs`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000002_create_jobs_table.php (create)`, `0001_01_01_000002_create_jobs_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('failed_jobs_id_seq'::regclass)` | — |
| 2 | `uuid` | `character varying(255)` | No | `—` | — |
| 3 | `connection` | `character varying(255)` | No | `—` | — |
| 4 | `queue` | `character varying(255)` | No | `—` | — |
| 5 | `payload` | `text` | No | `—` | — |
| 6 | `exception` | `text` | No | `—` | — |
| 7 | `failed_at` | `timestamp(0) without time zone` | No | `CURRENT_TIMESTAMP` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `failed_jobs_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `failed_jobs_uuid_unique` | UNIQUE | `UNIQUE (uuid)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `failed_jobs_connection_queue_failed_at_index` | `CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at)` |
| `failed_jobs_pkey` | `CREATE UNIQUE INDEX failed_jobs_pkey ON public.failed_jobs USING btree (id)` |
| `failed_jobs_uuid_unique` | `CREATE UNIQUE INDEX failed_jobs_uuid_unique ON public.failed_jobs USING btree (uuid)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `job_batches`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000002_create_jobs_table.php (create)`, `0001_01_01_000002_create_jobs_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `character varying(255)` | No | `—` | — |
| 2 | `name` | `character varying(255)` | No | `—` | — |
| 3 | `total_jobs` | `integer` | No | `—` | — |
| 4 | `pending_jobs` | `integer` | No | `—` | — |
| 5 | `failed_jobs` | `integer` | No | `—` | — |
| 6 | `failed_job_ids` | `text` | No | `—` | — |
| 7 | `options` | `text` | Yes | `—` | — |
| 8 | `cancelled_at` | `integer` | Yes | `—` | — |
| 9 | `created_at` | `integer` | No | `—` | — |
| 10 | `finished_at` | `integer` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `job_batches_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `job_batches_pkey` | `CREATE UNIQUE INDEX job_batches_pkey ON public.job_batches USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `jobs`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000002_create_jobs_table.php (create)`, `0001_01_01_000002_create_jobs_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('jobs_id_seq'::regclass)` | — |
| 2 | `queue` | `character varying(255)` | No | `—` | — |
| 3 | `payload` | `text` | No | `—` | — |
| 4 | `attempts` | `smallint` | No | `—` | — |
| 5 | `reserved_at` | `integer` | Yes | `—` | — |
| 6 | `available_at` | `integer` | No | `—` | — |
| 7 | `created_at` | `integer` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `jobs_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `jobs_pkey` | `CREATE UNIQUE INDEX jobs_pkey ON public.jobs USING btree (id)` |
| `jobs_queue_index` | `CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `migrations`

- Current rows: **50**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: No direct `Schema::create`, `Schema::table`, or `Schema::dropIfExists` reference was detected in the repository migration files.

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `integer` | No | `nextval('migrations_id_seq'::regclass)` | — |
| 2 | `migration` | `character varying(255)` | No | `—` | — |
| 3 | `batch` | `integer` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `migrations_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `migrations_pkey` | `CREATE UNIQUE INDEX migrations_pkey ON public.migrations USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `milestone_definitions`

- Current rows: **13**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_11_000002_create_group_owned_research_progress_tables.php (create)`, `2026_08_11_000002_create_group_owned_research_progress_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('milestone_definitions_id_seq'::regclass)` | — |
| 2 | `code` | `character varying(80)` | No | `—` | — |
| 3 | `name` | `character varying(150)` | No | `—` | — |
| 4 | `description` | `text` | Yes | `—` | — |
| 5 | `sequence` | `smallint` | No | `—` | — |
| 6 | `weight` | `numeric(8,4)` | No | `'1'::numeric` | — |
| 7 | `is_active` | `boolean` | No | `true` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `milestone_definitions_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `milestone_definitions_code_unique` | UNIQUE | `UNIQUE (code)` |
| `milestone_definitions_sequence_unique` | UNIQUE | `UNIQUE (sequence)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `milestone_definitions_code_unique` | `CREATE UNIQUE INDEX milestone_definitions_code_unique ON public.milestone_definitions USING btree (code)` |
| `milestone_definitions_is_active_sequence_index` | `CREATE INDEX milestone_definitions_is_active_sequence_index ON public.milestone_definitions USING btree (is_active, sequence)` |
| `milestone_definitions_pkey` | `CREATE UNIQUE INDEX milestone_definitions_pkey ON public.milestone_definitions USING btree (id)` |
| `milestone_definitions_sequence_unique` | `CREATE UNIQUE INDEX milestone_definitions_sequence_unique ON public.milestone_definitions USING btree (sequence)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `milestone_evidences`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_11_000002_create_group_owned_research_progress_tables.php (create)`, `2026_08_11_000002_create_group_owned_research_progress_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('milestone_evidences_id_seq'::regclass)` | — |
| 2 | `research_group_milestone_id` | `bigint` | No | `—` | — |
| 3 | `evidence_type` | `character varying(40)` | No | `—` | — |
| 4 | `evidence_id` | `bigint` | No | `—` | — |
| 5 | `linked_by` | `bigint` | Yes | `—` | — |
| 6 | `summary` | `character varying(500)` | Yes | `—` | — |
| 7 | `linked_at` | `timestamp(0) with time zone` | No | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `milestone_evidences_linked_by_foreign` | FOREIGN KEY | `FOREIGN KEY (linked_by) REFERENCES users(id) ON DELETE SET NULL` |
| `milestone_evidences_research_group_milestone_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_group_milestone_id) REFERENCES research_group_milestones(id) ON DELETE RESTRICT` |
| `milestone_evidences_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `milestone_evidence_unique` | UNIQUE | `UNIQUE (research_group_milestone_id, evidence_type, evidence_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `milestone_evidence_unique` | `CREATE UNIQUE INDEX milestone_evidence_unique ON public.milestone_evidences USING btree (research_group_milestone_id, evidence_type, evidence_id)` |
| `milestone_evidences_evidence_type_evidence_id_index` | `CREATE INDEX milestone_evidences_evidence_type_evidence_id_index ON public.milestone_evidences USING btree (evidence_type, evidence_id)` |
| `milestone_evidences_pkey` | `CREATE UNIQUE INDEX milestone_evidences_pkey ON public.milestone_evidences USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `model_has_permissions`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: No direct `Schema::create`, `Schema::table`, or `Schema::dropIfExists` reference was detected in the repository migration files.

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `permission_id` | `bigint` | No | `—` | — |
| 2 | `model_type` | `character varying(255)` | No | `—` | — |
| 3 | `model_id` | `bigint` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `model_has_permissions_permission_id_foreign` | FOREIGN KEY | `FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE` |
| `model_has_permissions_pkey` | PRIMARY KEY | `PRIMARY KEY (permission_id, model_id, model_type)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `model_has_permissions_model_id_model_type_index` | `CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type)` |
| `model_has_permissions_pkey` | `CREATE UNIQUE INDEX model_has_permissions_pkey ON public.model_has_permissions USING btree (permission_id, model_id, model_type)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `model_has_roles`

- Current rows: **34**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: No direct `Schema::create`, `Schema::table`, or `Schema::dropIfExists` reference was detected in the repository migration files.

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `role_id` | `bigint` | No | `—` | — |
| 2 | `model_type` | `character varying(255)` | No | `—` | — |
| 3 | `model_id` | `bigint` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `model_has_roles_role_id_foreign` | FOREIGN KEY | `FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE` |
| `model_has_roles_pkey` | PRIMARY KEY | `PRIMARY KEY (role_id, model_id, model_type)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `model_has_roles_model_id_model_type_index` | `CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type)` |
| `model_has_roles_pkey` | `CREATE UNIQUE INDEX model_has_roles_pkey ON public.model_has_roles USING btree (role_id, model_id, model_type)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `notifications`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_26_000001_create_document_review_integration_tables.php (create)`, `2026_07_26_000001_create_document_review_integration_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `uuid` | No | `—` | — |
| 2 | `type` | `character varying(255)` | No | `—` | — |
| 3 | `notifiable_type` | `character varying(255)` | No | `—` | — |
| 4 | `notifiable_id` | `bigint` | No | `—` | — |
| 5 | `data` | `json` | No | `—` | — |
| 6 | `read_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `notifications_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `notifications_notifiable_type_notifiable_id_index` | `CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id)` |
| `notifications_pkey` | `CREATE UNIQUE INDEX notifications_pkey ON public.notifications USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `official_form_actor_assignments`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_12_000001_create_official_form_catalog_tables.php (create)`, `2026_08_12_000001_create_official_form_catalog_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('official_form_actor_assignments_id_seq'::regclass)` | — |
| 2 | `official_form_instance_id` | `bigint` | No | `—` | — |
| 3 | `user_id` | `bigint` | No | `—` | — |
| 4 | `actor_type` | `character varying(64)` | No | `—` | — |
| 5 | `assigned_by` | `bigint` | Yes | `—` | — |
| 6 | `assigned_at` | `timestamp(0) without time zone` | No | `—` | — |
| 7 | `status` | `character varying(32)` | No | `'active'::character varying` | — |
| 8 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `official_form_actor_assignments_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `official_form_actor_assignments_official_form_instance_id_forei` | FOREIGN KEY | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `official_form_actor_assignments_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_actor_assignments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `form_actor_assignment_unique` | UNIQUE | `UNIQUE (official_form_instance_id, actor_type, user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `form_actor_assignment_unique` | `CREATE UNIQUE INDEX form_actor_assignment_unique ON public.official_form_actor_assignments USING btree (official_form_instance_id, actor_type, user_id)` |
| `official_form_actor_assignments_pkey` | `CREATE UNIQUE INDEX official_form_actor_assignments_pkey ON public.official_form_actor_assignments USING btree (id)` |
| `official_form_actor_assignments_user_id_actor_type_status_index` | `CREATE INDEX official_form_actor_assignments_user_id_actor_type_status_index ON public.official_form_actor_assignments USING btree (user_id, actor_type, status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `official_form_definitions`

- Current rows: **25**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_12_000001_create_official_form_catalog_tables.php (create)`, `2026_08_12_000001_create_official_form_catalog_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('official_form_definitions_id_seq'::regclass)` | — |
| 2 | `code` | `character varying(32)` | No | `—` | — |
| 3 | `title` | `character varying(255)` | No | `—` | — |
| 4 | `description` | `text` | Yes | `—` | — |
| 5 | `default_category` | `character varying(64)` | No | `—` | — |
| 6 | `ownership_scope` | `character varying(32)` | No | `'research_group'::character varying` | — |
| 7 | `cardinality` | `character varying(32)` | No | `'single_per_group'::character varying` | — |
| 8 | `template_view` | `character varying(150)` | No | `—` | — |
| 9 | `is_active` | `boolean` | No | `true` | — |
| 10 | `sort_order` | `smallint` | No | `'0'::smallint` | — |
| 11 | `metadata` | `json` | Yes | `—` | — |
| 12 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 13 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `official_form_definitions_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `official_form_definitions_code_unique` | UNIQUE | `UNIQUE (code)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `official_form_definitions_code_unique` | `CREATE UNIQUE INDEX official_form_definitions_code_unique ON public.official_form_definitions USING btree (code)` |
| `official_form_definitions_pkey` | `CREATE UNIQUE INDEX official_form_definitions_pkey ON public.official_form_definitions USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `official_form_instances`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_12_000001_create_official_form_catalog_tables.php (create)`, `2026_08_12_000001_create_official_form_catalog_tables.php (alter)`, `2026_08_12_000001_create_official_form_catalog_tables.php (drop in down/cleanup path)`, `2026_08_16_000003_add_evaluation_link_to_official_form_instances.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('official_form_instances_id_seq'::regclass)` | — |
| 2 | `official_form_definition_id` | `bigint` | No | `—` | — |
| 3 | `research_class_group_id` | `bigint` | Yes | `—` | — |
| 4 | `research_class_id` | `bigint` | Yes | `—` | — |
| 5 | `context_key` | `character varying(64)` | No | `'general'::character varying` | — |
| 6 | `source_type` | `character varying(255)` | Yes | `—` | — |
| 7 | `source_id` | `bigint` | Yes | `—` | — |
| 8 | `initiated_by` | `bigint` | No | `—` | — |
| 9 | `status` | `character varying(32)` | No | `'draft'::character varying` | — |
| 10 | `current_version_id` | `bigint` | Yes | `—` | — |
| 11 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 12 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 13 | `defense_evaluation_id` | `bigint` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `official_form_instances_current_version_id_foreign` | FOREIGN KEY | `FOREIGN KEY (current_version_id) REFERENCES official_form_versions(id) ON DELETE SET NULL` |
| `official_form_instances_defense_evaluation_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_evaluation_id) REFERENCES defense_evaluations(id) ON DELETE RESTRICT` |
| `official_form_instances_initiated_by_foreign` | FOREIGN KEY | `FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_instances_official_form_definition_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_definition_id) REFERENCES official_form_definitions(id) ON DELETE RESTRICT` |
| `official_form_instances_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE SET NULL` |
| `official_form_instances_research_class_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE SET NULL` |
| `official_form_instances_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `idx_form_instance_class_lookup` | `CREATE INDEX idx_form_instance_class_lookup ON public.official_form_instances USING btree (research_class_id, official_form_definition_id, status)` |
| `idx_form_instance_group_lookup` | `CREATE INDEX idx_form_instance_group_lookup ON public.official_form_instances USING btree (research_class_group_id, official_form_definition_id, status)` |
| `idx_form_instance_source` | `CREATE INDEX idx_form_instance_source ON public.official_form_instances USING btree (source_type, source_id)` |
| `official_form_instances_defense_evaluation_id_index` | `CREATE INDEX official_form_instances_defense_evaluation_id_index ON public.official_form_instances USING btree (defense_evaluation_id)` |
| `official_form_instances_pkey` | `CREATE UNIQUE INDEX official_form_instances_pkey ON public.official_form_instances USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `official_form_signatures`

- Current rows: **5**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_14_000001_create_official_form_signatures_tables.php (create)`, `2026_08_14_000001_create_official_form_signatures_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('official_form_signatures_id_seq'::regclass)` | — |
| 2 | `official_form_instance_id` | `bigint` | No | `—` | — |
| 3 | `official_form_version_id` | `bigint` | No | `—` | — |
| 4 | `signer_user_id` | `bigint` | No | `—` | — |
| 5 | `user_signature_id` | `bigint` | Yes | `—` | — |
| 6 | `actor_type` | `character varying(64)` | No | `—` | — |
| 7 | `academic_action` | `character varying(64)` | No | `—` | — |
| 8 | `signer_name_snapshot` | `character varying(255)` | No | `—` | — |
| 9 | `signer_email_snapshot` | `character varying(255)` | No | `—` | — |
| 10 | `signature_storage_disk` | `character varying(50)` | No | `'local'::character varying` | — |
| 11 | `signature_storage_path` | `text` | No | `—` | — |
| 12 | `signature_sha256` | `character(64)` | No | `—` | — |
| 13 | `version_payload_sha256` | `character(64)` | No | `—` | — |
| 14 | `attestation_hash` | `character(64)` | No | `—` | — |
| 15 | `attestation_key_version` | `character varying(16)` | No | `'v1'::character varying` | — |
| 16 | `signed_at` | `timestamp(0) with time zone` | No | `—` | — |
| 17 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 18 | `user_agent` | `text` | Yes | `—` | — |
| 19 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 20 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `official_form_signatures_official_form_instance_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `official_form_signatures_official_form_version_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_version_id) REFERENCES official_form_versions(id) ON DELETE RESTRICT` |
| `official_form_signatures_signer_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (signer_user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_signatures_user_signature_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_signature_id) REFERENCES user_signatures(id) ON DELETE SET NULL` |
| `official_form_signatures_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `idx_official_form_signatures_unique` | UNIQUE | `UNIQUE (official_form_version_id, signer_user_id, actor_type, academic_action)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `idx_form_signatures_instance_version` | `CREATE INDEX idx_form_signatures_instance_version ON public.official_form_signatures USING btree (official_form_instance_id, official_form_version_id)` |
| `idx_official_form_signatures_unique` | `CREATE UNIQUE INDEX idx_official_form_signatures_unique ON public.official_form_signatures USING btree (official_form_version_id, signer_user_id, actor_type, academic_action)` |
| `official_form_signatures_pkey` | `CREATE UNIQUE INDEX official_form_signatures_pkey ON public.official_form_signatures USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `official_form_verifications`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_14_000001_create_official_form_signatures_tables.php (create)`, `2026_08_14_000001_create_official_form_signatures_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('official_form_verifications_id_seq'::regclass)` | — |
| 2 | `official_form_version_id` | `bigint` | No | `—` | — |
| 3 | `public_reference` | `character(36)` | No | `—` | — |
| 4 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 5 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `official_form_verifications_official_form_version_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_version_id) REFERENCES official_form_versions(id) ON DELETE RESTRICT` |
| `official_form_verifications_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `official_form_verifications_official_form_version_id_unique` | UNIQUE | `UNIQUE (official_form_version_id)` |
| `official_form_verifications_public_reference_unique` | UNIQUE | `UNIQUE (public_reference)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `official_form_verifications_official_form_version_id_unique` | `CREATE UNIQUE INDEX official_form_verifications_official_form_version_id_unique ON public.official_form_verifications USING btree (official_form_version_id)` |
| `official_form_verifications_pkey` | `CREATE UNIQUE INDEX official_form_verifications_pkey ON public.official_form_verifications USING btree (id)` |
| `official_form_verifications_public_reference_unique` | `CREATE UNIQUE INDEX official_form_verifications_public_reference_unique ON public.official_form_verifications USING btree (public_reference)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `official_form_versions`

- Current rows: **2**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_12_000001_create_official_form_catalog_tables.php (create)`, `2026_08_12_000001_create_official_form_catalog_tables.php (drop in down/cleanup path)`, `2026_08_15_000006_add_source_snapshot_to_official_form_versions_table.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('official_form_versions_id_seq'::regclass)` | — |
| 2 | `official_form_instance_id` | `bigint` | No | `—` | — |
| 3 | `version_number` | `integer` | No | `—` | — |
| 4 | `payload` | `json` | No | `—` | — |
| 5 | `created_by` | `bigint` | No | `—` | — |
| 6 | `supersedes_version_id` | `bigint` | Yes | `—` | — |
| 7 | `is_current` | `boolean` | No | `true` | — |
| 8 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 10 | `source_snapshot` | `json` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `official_form_versions_created_by_foreign` | FOREIGN KEY | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `official_form_versions_official_form_instance_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `official_form_versions_supersedes_version_id_foreign` | FOREIGN KEY | `FOREIGN KEY (supersedes_version_id) REFERENCES official_form_versions(id) ON DELETE SET NULL` |
| `official_form_versions_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `official_form_versions_official_form_instance_id_version_number` | UNIQUE | `UNIQUE (official_form_instance_id, version_number)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `official_form_versions_official_form_instance_id_is_current_ind` | `CREATE INDEX official_form_versions_official_form_instance_id_is_current_ind ON public.official_form_versions USING btree (official_form_instance_id, is_current)` |
| `official_form_versions_official_form_instance_id_version_number` | `CREATE UNIQUE INDEX official_form_versions_official_form_instance_id_version_number ON public.official_form_versions USING btree (official_form_instance_id, version_number)` |
| `official_form_versions_pkey` | `CREATE UNIQUE INDEX official_form_versions_pkey ON public.official_form_versions USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `password_reset_tokens`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000000_create_users_table.php (create)`, `0001_01_01_000000_create_users_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `email` | `character varying(255)` | No | `—` | — |
| 2 | `token` | `character varying(255)` | No | `—` | — |
| 3 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `password_reset_tokens_pkey` | PRIMARY KEY | `PRIMARY KEY (email)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `password_reset_tokens_pkey` | `CREATE UNIQUE INDEX password_reset_tokens_pkey ON public.password_reset_tokens USING btree (email)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `permissions`

- Current rows: **113**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_05_000003_add_dynamic_rbac_metadata.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('permissions_id_seq'::regclass)` | — |
| 2 | `name` | `character varying(255)` | No | `—` | — |
| 3 | `guard_name` | `character varying(255)` | No | `—` | — |
| 4 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 5 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 6 | `display_name` | `character varying(255)` | Yes | `—` | — |
| 7 | `description` | `text` | Yes | `—` | — |
| 8 | `module` | `character varying(255)` | Yes | `—` | — |
| 9 | `scope` | `character varying(255)` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `permissions_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `permissions_name_guard_name_unique` | UNIQUE | `UNIQUE (name, guard_name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `permissions_module_index` | `CREATE INDEX permissions_module_index ON public.permissions USING btree (module)` |
| `permissions_name_guard_name_unique` | `CREATE UNIQUE INDEX permissions_name_guard_name_unique ON public.permissions USING btree (name, guard_name)` |
| `permissions_pkey` | `CREATE UNIQUE INDEX permissions_pkey ON public.permissions USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `programs`

- Current rows: **8**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('programs_id_seq'::regclass)` | — |
| 2 | `department_id` | `bigint` | No | `—` | — |
| 3 | `code` | `character varying(30)` | No | `—` | — |
| 4 | `name` | `character varying(255)` | No | `—` | — |
| 5 | `degree_level` | `character varying(50)` | Yes | `—` | — |
| 6 | `is_active` | `boolean` | No | `true` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `programs_department_id_foreign` | FOREIGN KEY | `FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT` |
| `programs_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `programs_code_unique` | UNIQUE | `UNIQUE (code)` |
| `programs_department_id_name_unique` | UNIQUE | `UNIQUE (department_id, name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `programs_code_unique` | `CREATE UNIQUE INDEX programs_code_unique ON public.programs USING btree (code)` |
| `programs_department_id_name_unique` | `CREATE UNIQUE INDEX programs_department_id_name_unique ON public.programs USING btree (department_id, name)` |
| `programs_pkey` | `CREATE UNIQUE INDEX programs_pkey ON public.programs USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_class_actor_assignments`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_13_000001_create_research_class_actor_assignments_table.php (create)`, `2026_08_13_000001_create_research_class_actor_assignments_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_actor_assignments_id_seq'::regclass)` | — |
| 2 | `research_class_id` | `bigint` | No | `—` | — |
| 3 | `user_id` | `bigint` | No | `—` | — |
| 4 | `actor_type` | `character varying(64)` | No | `—` | — |
| 5 | `assigned_by` | `bigint` | Yes | `—` | — |
| 6 | `assigned_at` | `timestamp(0) without time zone` | No | `—` | — |
| 7 | `status` | `character varying(32)` | No | `'active'::character varying` | — |
| 8 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_actor_assignments_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_actor_assignments_research_class_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_actor_assignments_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_actor_assignments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `class_actor_assignment_unique` | UNIQUE | `UNIQUE (research_class_id, actor_type, user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `class_actor_assignment_unique` | `CREATE UNIQUE INDEX class_actor_assignment_unique ON public.research_class_actor_assignments USING btree (research_class_id, actor_type, user_id)` |
| `class_actor_user_lookup` | `CREATE INDEX class_actor_user_lookup ON public.research_class_actor_assignments USING btree (user_id, actor_type, status)` |
| `research_class_actor_assignments_pkey` | `CREATE UNIQUE INDEX research_class_actor_assignments_pkey ON public.research_class_actor_assignments USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_class_enrollments`

- Current rows: **4**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_25_000002_create_research_class_enrollments_table.php (create)`, `2026_07_25_000002_create_research_class_enrollments_table.php (drop in down/cleanup path)`, `2026_07_25_000003_add_join_request_workflow_to_research_class_enrollments.php (alter)`, `2026_08_08_000001_allow_research_class_join_request_history.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_enrollments_id_seq'::regclass)` | — |
| 2 | `research_class_id` | `bigint` | No | `—` | — |
| 3 | `student_id` | `bigint` | No | `—` | — |
| 4 | `status` | `character varying(20)` | No | `'pending'::character varying` | — |
| 5 | `joined_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 6 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `requested_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `reviewed_by` | `bigint` | Yes | `—` | — |
| 10 | `reviewed_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_enrollments_research_class_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_enrollments_reviewed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_enrollments_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `research_class_enrollments_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `class_enrollments_request_queue_index` | `CREATE INDEX class_enrollments_request_queue_index ON public.research_class_enrollments USING btree (research_class_id, status, requested_at)` |
| `class_enrollments_student_class_status_reviewed_index` | `CREATE INDEX class_enrollments_student_class_status_reviewed_index ON public.research_class_enrollments USING btree (student_id, research_class_id, status, reviewed_at)` |
| `class_enrollments_student_status_requested_index` | `CREATE INDEX class_enrollments_student_status_requested_index ON public.research_class_enrollments USING btree (student_id, status, requested_at)` |
| `research_class_enrollments_pkey` | `CREATE UNIQUE INDEX research_class_enrollments_pkey ON public.research_class_enrollments USING btree (id)` |
| `research_class_enrollments_student_id_status_index` | `CREATE INDEX research_class_enrollments_student_id_status_index ON public.research_class_enrollments USING btree (student_id, status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_class_group_adviser_histories`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php (create)`, `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_group_adviser_histories_id_seq'::regclass)` | — |
| 2 | `research_class_group_id` | `bigint` | No | `—` | — |
| 3 | `adviser_id` | `bigint` | No | `—` | — |
| 4 | `assigned_by` | `bigint` | No | `—` | — |
| 5 | `assigned_at` | `timestamp(0) with time zone` | No | `—` | — |
| 6 | `ended_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `ended_by` | `bigint` | Yes | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_group_adviser_histories_adviser_id_foreign` | FOREIGN KEY | `FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_histories_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_histories_ended_by_foreign` | FOREIGN KEY | `FOREIGN KEY (ended_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_group_adviser_histories_research_class_group_id_` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `research_class_group_adviser_histories_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_class_group_adviser_histories_pkey` | `CREATE UNIQUE INDEX research_class_group_adviser_histories_pkey ON public.research_class_group_adviser_histories USING btree (id)` |
| `research_class_group_adviser_histories_research_class_group_id_` | `CREATE INDEX research_class_group_adviser_histories_research_class_group_id_ ON public.research_class_group_adviser_histories USING btree (research_class_group_id, adviser_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_class_group_adviser_requests`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php (create)`, `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_group_adviser_requests_id_seq'::regclass)` | — |
| 2 | `research_class_group_id` | `bigint` | No | `—` | — |
| 3 | `adviser_id` | `bigint` | No | `—` | — |
| 4 | `requested_by` | `bigint` | No | `—` | — |
| 5 | `status` | `character varying(20)` | No | `'pending'::character varying` | — |
| 6 | `requested_at` | `timestamp(0) with time zone` | No | `—` | — |
| 7 | `responded_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `cancelled_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_group_adviser_requests_adviser_id_foreign` | FOREIGN KEY | `FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_requests_requested_by_foreign` | FOREIGN KEY | `FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_adviser_requests_research_class_group_id_f` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `research_class_group_adviser_requests_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_class_group_adviser_requests_adviser_id_status_index` | `CREATE INDEX research_class_group_adviser_requests_adviser_id_status_index ON public.research_class_group_adviser_requests USING btree (adviser_id, status)` |
| `research_class_group_adviser_requests_pkey` | `CREATE UNIQUE INDEX research_class_group_adviser_requests_pkey ON public.research_class_group_adviser_requests USING btree (id)` |
| `research_class_group_adviser_requests_research_class_group_id_s` | `CREATE INDEX research_class_group_adviser_requests_research_class_group_id_s ON public.research_class_group_adviser_requests USING btree (research_class_group_id, status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_class_group_member_histories`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_09_000001_create_phase14_repository_foundation.php (create)`, `2026_08_09_000001_create_phase14_repository_foundation.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_group_member_histories_id_seq'::regclass)` | — |
| 2 | `research_class_group_id` | `bigint` | No | `—` | — |
| 3 | `research_class_id` | `bigint` | No | `—` | — |
| 4 | `research_class_enrollment_id` | `bigint` | Yes | `—` | — |
| 5 | `student_id` | `bigint` | No | `—` | — |
| 6 | `assigned_by` | `bigint` | Yes | `—` | — |
| 7 | `joined_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `archived_at` | `timestamp(0) with time zone` | No | `—` | — |
| 9 | `archive_reason` | `character varying(32)` | No | `'group_disbanded'::character varying` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_group_member_histories_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_group_member_histories_research_class_enrollment` | FOREIGN KEY | `FOREIGN KEY (research_class_enrollment_id) REFERENCES research_class_enrollments(id) ON DELETE SET NULL` |
| `research_class_group_member_histories_research_class_group_id_f` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `research_class_group_member_histories_research_class_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE RESTRICT` |
| `research_class_group_member_histories_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_member_histories_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `group_member_history_unique` | UNIQUE | `UNIQUE (research_class_group_id, student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `group_member_history_unique` | `CREATE UNIQUE INDEX group_member_history_unique ON public.research_class_group_member_histories USING btree (research_class_group_id, student_id)` |
| `research_class_group_member_histories_pkey` | `CREATE UNIQUE INDEX research_class_group_member_histories_pkey ON public.research_class_group_member_histories USING btree (id)` |
| `research_class_group_member_histories_student_id_archived_at_in` | `CREATE INDEX research_class_group_member_histories_student_id_archived_at_in ON public.research_class_group_member_histories USING btree (student_id, archived_at)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `research_class_group_members`

- Current rows: **4**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_04_000001_refactor_classes_for_facilitator_grouping.php (create)`, `2026_08_04_000001_refactor_classes_for_facilitator_grouping.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_group_members_id_seq'::regclass)` | — |
| 2 | `research_class_group_id` | `bigint` | No | `—` | — |
| 3 | `research_class_id` | `bigint` | No | `—` | — |
| 4 | `research_class_enrollment_id` | `bigint` | No | `—` | — |
| 5 | `student_id` | `bigint` | No | `—` | — |
| 6 | `assigned_by` | `bigint` | No | `—` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_group_members_assigned_by_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_group_members_research_class_enrollment_id_forei` | FOREIGN KEY | `FOREIGN KEY (research_class_enrollment_id) REFERENCES research_class_enrollments(id) ON DELETE CASCADE` |
| `research_class_group_members_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE CASCADE` |
| `research_class_group_members_research_class_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_group_members_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE` |
| `research_class_group_members_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `research_class_group_members_research_class_enrollment_id_uniqu` | UNIQUE | `UNIQUE (research_class_enrollment_id)` |
| `research_class_group_members_research_class_group_id_student_id` | UNIQUE | `UNIQUE (research_class_group_id, student_id)` |
| `research_class_group_members_research_class_id_student_id_uniqu` | UNIQUE | `UNIQUE (research_class_id, student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_class_group_members_pkey` | `CREATE UNIQUE INDEX research_class_group_members_pkey ON public.research_class_group_members USING btree (id)` |
| `research_class_group_members_research_class_enrollment_id_uniqu` | `CREATE UNIQUE INDEX research_class_group_members_research_class_enrollment_id_uniqu ON public.research_class_group_members USING btree (research_class_enrollment_id)` |
| `research_class_group_members_research_class_group_id_created_at` | `CREATE INDEX research_class_group_members_research_class_group_id_created_at ON public.research_class_group_members USING btree (research_class_group_id, created_at)` |
| `research_class_group_members_research_class_group_id_student_id` | `CREATE UNIQUE INDEX research_class_group_members_research_class_group_id_student_id ON public.research_class_group_members USING btree (research_class_group_id, student_id)` |
| `research_class_group_members_research_class_id_student_id_uniqu` | `CREATE UNIQUE INDEX research_class_group_members_research_class_id_student_id_uniqu ON public.research_class_group_members USING btree (research_class_id, student_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_class_groups`

- Current rows: **2**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_04_000001_refactor_classes_for_facilitator_grouping.php (create)`, `2026_08_04_000001_refactor_classes_for_facilitator_grouping.php (drop in down/cleanup path)`, `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables.php (alter)`, `2026_08_08_000002_add_group_leader_and_document_ownership_fields.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_class_groups_id_seq'::regclass)` | — |
| 2 | `research_class_id` | `bigint` | No | `—` | — |
| 3 | `research_group_id` | `bigint` | Yes | `—` | — |
| 4 | `creation_token` | `uuid` | No | `—` | — |
| 5 | `name` | `character varying(120)` | No | `—` | — |
| 6 | `adviser_id` | `bigint` | Yes | `—` | — |
| 7 | `created_by` | `bigint` | No | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `status` | `character varying(20)` | No | `'active'::character varying` | — |
| 11 | `disbanded_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 12 | `leader_student_id` | `bigint` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_class_groups_adviser_id_foreign` | FOREIGN KEY | `FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_groups_created_by_foreign` | FOREIGN KEY | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `research_class_groups_leader_student_id_foreign` | FOREIGN KEY | `FOREIGN KEY (leader_student_id) REFERENCES users(id) ON DELETE SET NULL` |
| `research_class_groups_research_class_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_id) REFERENCES research_classes(id) ON DELETE CASCADE` |
| `research_class_groups_research_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_group_id) REFERENCES research_groups(id) ON DELETE SET NULL` |
| `research_class_groups_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `research_class_groups_research_class_id_creation_token_unique` | UNIQUE | `UNIQUE (research_class_id, creation_token)` |
| `research_class_groups_research_class_id_name_unique` | UNIQUE | `UNIQUE (research_class_id, name)` |
| `research_class_groups_research_group_id_unique` | UNIQUE | `UNIQUE (research_group_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_class_groups_adviser_id_research_class_id_index` | `CREATE INDEX research_class_groups_adviser_id_research_class_id_index ON public.research_class_groups USING btree (adviser_id, research_class_id)` |
| `research_class_groups_pkey` | `CREATE UNIQUE INDEX research_class_groups_pkey ON public.research_class_groups USING btree (id)` |
| `research_class_groups_research_class_id_creation_token_unique` | `CREATE UNIQUE INDEX research_class_groups_research_class_id_creation_token_unique ON public.research_class_groups USING btree (research_class_id, creation_token)` |
| `research_class_groups_research_class_id_name_unique` | `CREATE UNIQUE INDEX research_class_groups_research_class_id_name_unique ON public.research_class_groups USING btree (research_class_id, name)` |
| `research_class_groups_research_class_id_status_index` | `CREATE INDEX research_class_groups_research_class_id_status_index ON public.research_class_groups USING btree (research_class_id, status)` |
| `research_class_groups_research_group_id_unique` | `CREATE UNIQUE INDEX research_class_groups_research_group_id_unique ON public.research_class_groups USING btree (research_group_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_classes`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_25_000001_create_research_classes_table.php (create)`, `2026_07_25_000001_create_research_classes_table.php (drop in down/cleanup path)`, `2026_08_04_000001_refactor_classes_for_facilitator_grouping.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_classes_id_seq'::regclass)` | — |
| 2 | `facilitator_id` | `bigint` | No | `—` | — |
| 3 | `creation_token` | `uuid` | No | `—` | — |
| 4 | `name` | `character varying(120)` | No | `—` | — |
| 5 | `description` | `text` | Yes | `—` | — |
| 6 | `join_code_hash` | `character(64)` | No | `—` | — |
| 7 | `join_code_encrypted` | `text` | No | `—` | — |
| 8 | `max_students` | `smallint` | No | `'50'::smallint` | — |
| 9 | `is_active` | `boolean` | No | `true` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_classes_adviser_id_foreign` | FOREIGN KEY | `FOREIGN KEY (facilitator_id) REFERENCES users(id) ON DELETE CASCADE` |
| `research_classes_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `research_classes_adviser_id_creation_token_unique` | UNIQUE | `UNIQUE (facilitator_id, creation_token)` |
| `research_classes_join_code_hash_unique` | UNIQUE | `UNIQUE (join_code_hash)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_classes_adviser_id_creation_token_unique` | `CREATE UNIQUE INDEX research_classes_adviser_id_creation_token_unique ON public.research_classes USING btree (facilitator_id, creation_token)` |
| `research_classes_adviser_id_is_active_index` | `CREATE INDEX research_classes_adviser_id_is_active_index ON public.research_classes USING btree (facilitator_id, is_active)` |
| `research_classes_join_code_hash_unique` | `CREATE UNIQUE INDEX research_classes_join_code_hash_unique ON public.research_classes USING btree (join_code_hash)` |
| `research_classes_pkey` | `CREATE UNIQUE INDEX research_classes_pkey ON public.research_classes USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_group_members`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_group_members_id_seq'::regclass)` | — |
| 2 | `research_group_id` | `bigint` | No | `—` | — |
| 3 | `student_profile_id` | `bigint` | No | `—` | — |
| 4 | `member_role` | `character varying(30)` | No | `'member'::character varying` | — |
| 5 | `joined_at` | `timestamp(0) with time zone` | No | `CURRENT_TIMESTAMP` | — |
| 6 | `left_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_group_members_research_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_group_id) REFERENCES research_groups(id) ON DELETE CASCADE` |
| `research_group_members_student_profile_id_foreign` | FOREIGN KEY | `FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE` |
| `research_group_members_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `research_group_members_research_group_id_student_profile_id_uni` | UNIQUE | `UNIQUE (research_group_id, student_profile_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_group_members_pkey` | `CREATE UNIQUE INDEX research_group_members_pkey ON public.research_group_members USING btree (id)` |
| `research_group_members_research_group_id_student_profile_id_uni` | `CREATE UNIQUE INDEX research_group_members_research_group_id_student_profile_id_uni ON public.research_group_members USING btree (research_group_id, student_profile_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_group_milestone_events`

- Current rows: **6**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_11_000002_create_group_owned_research_progress_tables.php (create)`, `2026_08_11_000002_create_group_owned_research_progress_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_group_milestone_events_id_seq'::regclass)` | — |
| 2 | `research_group_milestone_id` | `bigint` | No | `—` | — |
| 3 | `actor_id` | `bigint` | Yes | `—` | — |
| 4 | `event` | `character varying(48)` | No | `—` | — |
| 5 | `from_status` | `character varying(32)` | Yes | `—` | — |
| 6 | `to_status` | `character varying(32)` | Yes | `—` | — |
| 7 | `reason` | `text` | Yes | `—` | — |
| 8 | `old_values` | `json` | Yes | `—` | — |
| 9 | `new_values` | `json` | Yes | `—` | — |
| 10 | `override_order` | `boolean` | No | `false` | — |
| 11 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 12 | `occurred_at` | `timestamp(0) with time zone` | No | `—` | — |
| 13 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_group_milestone_events_actor_id_foreign` | FOREIGN KEY | `FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestone_events_research_group_milestone_id_for` | FOREIGN KEY | `FOREIGN KEY (research_group_milestone_id) REFERENCES research_group_milestones(id) ON DELETE RESTRICT` |
| `research_group_milestone_events_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `milestone_event_history_index` | `CREATE INDEX milestone_event_history_index ON public.research_group_milestone_events USING btree (research_group_milestone_id, occurred_at)` |
| `research_group_milestone_events_actor_id_occurred_at_index` | `CREATE INDEX research_group_milestone_events_actor_id_occurred_at_index ON public.research_group_milestone_events USING btree (actor_id, occurred_at)` |
| `research_group_milestone_events_pkey` | `CREATE UNIQUE INDEX research_group_milestone_events_pkey ON public.research_group_milestone_events USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_group_milestones`

- Current rows: **26**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_11_000002_create_group_owned_research_progress_tables.php (create)`, `2026_08_11_000002_create_group_owned_research_progress_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_group_milestones_id_seq'::regclass)` | — |
| 2 | `research_class_group_id` | `bigint` | No | `—` | — |
| 3 | `milestone_definition_id` | `bigint` | No | `—` | — |
| 4 | `status` | `character varying(32)` | No | `'pending'::character varying` | — |
| 5 | `due_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 6 | `started_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `started_by` | `bigint` | Yes | `—` | — |
| 8 | `completed_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `completed_by` | `bigint` | Yes | `—` | — |
| 10 | `not_applicable_reason` | `text` | Yes | `—` | — |
| 11 | `remarks` | `text` | Yes | `—` | — |
| 12 | `updated_by` | `bigint` | Yes | `—` | — |
| 13 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 14 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_group_milestones_completed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestones_milestone_definition_id_foreign` | FOREIGN KEY | `FOREIGN KEY (milestone_definition_id) REFERENCES milestone_definitions(id) ON DELETE RESTRICT` |
| `research_group_milestones_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `research_group_milestones_started_by_foreign` | FOREIGN KEY | `FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestones_updated_by_foreign` | FOREIGN KEY | `FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_group_milestones_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `group_milestone_unique` | UNIQUE | `UNIQUE (research_class_group_id, milestone_definition_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `group_milestone_unique` | `CREATE UNIQUE INDEX group_milestone_unique ON public.research_group_milestones USING btree (research_class_group_id, milestone_definition_id)` |
| `research_group_milestones_pkey` | `CREATE UNIQUE INDEX research_group_milestones_pkey ON public.research_group_milestones USING btree (id)` |
| `research_group_milestones_research_class_group_id_status_index` | `CREATE INDEX research_group_milestones_research_class_group_id_status_index ON public.research_group_milestones USING btree (research_class_group_id, status)` |
| `research_group_milestones_status_due_at_index` | `CREATE INDEX research_group_milestones_status_due_at_index ON public.research_group_milestones USING btree (status, due_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_groups`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_groups_id_seq'::regclass)` | — |
| 2 | `program_id` | `bigint` | No | `—` | — |
| 3 | `academic_term_id` | `bigint` | No | `—` | — |
| 4 | `name` | `character varying(255)` | No | `—` | — |
| 5 | `created_by` | `bigint` | Yes | `—` | — |
| 6 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 7 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_groups_academic_term_id_foreign` | FOREIGN KEY | `FOREIGN KEY (academic_term_id) REFERENCES academic_terms(id) ON DELETE RESTRICT` |
| `research_groups_created_by_foreign` | FOREIGN KEY | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_groups_program_id_foreign` | FOREIGN KEY | `FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT` |
| `research_groups_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `research_groups_academic_term_id_name_unique` | UNIQUE | `UNIQUE (academic_term_id, name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_groups_academic_term_id_name_unique` | `CREATE UNIQUE INDEX research_groups_academic_term_id_name_unique ON public.research_groups USING btree (academic_term_id, name)` |
| `research_groups_pkey` | `CREATE UNIQUE INDEX research_groups_pkey ON public.research_groups USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_projects`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_projects_id_seq'::regclass)` | — |
| 2 | `research_group_id` | `bigint` | No | `—` | — |
| 3 | `title` | `character varying(500)` | No | `—` | — |
| 4 | `abstract` | `text` | Yes | `—` | — |
| 5 | `keywords` | `json` | No | `'[]'::json` | — |
| 6 | `category` | `character varying(150)` | Yes | `—` | — |
| 7 | `status` | `character varying(50)` | No | `'draft'::character varying` | — |
| 8 | `created_by` | `bigint` | Yes | `—` | — |
| 9 | `approved_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `completed_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `archived_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 12 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 13 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_projects_created_by_foreign` | FOREIGN KEY | `FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_projects_research_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_group_id) REFERENCES research_groups(id) ON DELETE RESTRICT` |
| `research_projects_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_projects_pkey` | `CREATE UNIQUE INDEX research_projects_pkey ON public.research_projects USING btree (id)` |
| `research_projects_research_group_id_index` | `CREATE INDEX research_projects_research_group_id_index ON public.research_projects USING btree (research_group_id)` |
| `research_projects_status_index` | `CREATE INDEX research_projects_status_index ON public.research_projects USING btree (status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `research_proposals`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_26_000001_create_document_review_integration_tables.php (create)`, `2026_07_26_000001_create_document_review_integration_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('research_proposals_id_seq'::regclass)` | — |
| 2 | `research_project_id` | `bigint` | Yes | `—` | — |
| 3 | `document_id` | `bigint` | Yes | `—` | — |
| 4 | `submitted_by` | `bigint` | No | `—` | — |
| 5 | `reviewed_by` | `bigint` | Yes | `—` | — |
| 6 | `version` | `integer` | No | `1` | — |
| 7 | `title` | `character varying(255)` | No | `—` | — |
| 8 | `status` | `character varying(32)` | No | `'pending'::character varying` | — |
| 9 | `review_notes` | `text` | Yes | `—` | — |
| 10 | `submitted_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `reviewed_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 12 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 13 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `research_proposals_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `research_proposals_reviewed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL` |
| `research_proposals_submitted_by_foreign` | FOREIGN KEY | `FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE` |
| `research_proposals_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `research_proposals_document_id_unique` | UNIQUE | `UNIQUE (document_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `research_proposals_document_id_unique` | `CREATE UNIQUE INDEX research_proposals_document_id_unique ON public.research_proposals USING btree (document_id)` |
| `research_proposals_pkey` | `CREATE UNIQUE INDEX research_proposals_pkey ON public.research_proposals USING btree (id)` |
| `research_proposals_research_project_id_version_index` | `CREATE INDEX research_proposals_research_project_id_version_index ON public.research_proposals USING btree (research_project_id, version)` |
| `research_proposals_status_reviewed_at_index` | `CREATE INDEX research_proposals_status_reviewed_at_index ON public.research_proposals USING btree (status, reviewed_at)` |
| `research_proposals_submitted_by_status_index` | `CREATE INDEX research_proposals_submitted_by_status_index ON public.research_proposals USING btree (submitted_by, status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `revision_request_events`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_27_000001_add_revision_workflow_tables.php (create)`, `2026_07_27_000001_add_revision_workflow_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('revision_request_events_id_seq'::regclass)` | — |
| 2 | `revision_request_id` | `bigint` | No | `—` | — |
| 3 | `actor_id` | `bigint` | No | `—` | — |
| 4 | `document_id` | `bigint` | Yes | `—` | — |
| 5 | `action` | `character varying(32)` | No | `—` | — |
| 6 | `from_status` | `character varying(32)` | Yes | `—` | — |
| 7 | `to_status` | `character varying(32)` | No | `—` | — |
| 8 | `notes` | `text` | Yes | `—` | — |
| 9 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 10 | `metadata` | `json` | Yes | `—` | — |
| 11 | `occurred_at` | `timestamp(0) with time zone` | No | `—` | — |
| 12 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 13 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `revision_request_events_actor_id_foreign` | FOREIGN KEY | `FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT` |
| `revision_request_events_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `revision_request_events_revision_request_id_foreign` | FOREIGN KEY | `FOREIGN KEY (revision_request_id) REFERENCES revision_requests(id) ON DELETE CASCADE` |
| `revision_request_events_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `revision_request_events_action_to_status_index` | `CREATE INDEX revision_request_events_action_to_status_index ON public.revision_request_events USING btree (action, to_status)` |
| `revision_request_events_actor_id_occurred_at_index` | `CREATE INDEX revision_request_events_actor_id_occurred_at_index ON public.revision_request_events USING btree (actor_id, occurred_at)` |
| `revision_request_events_pkey` | `CREATE UNIQUE INDEX revision_request_events_pkey ON public.revision_request_events USING btree (id)` |
| `revision_request_events_revision_request_id_occurred_at_index` | `CREATE INDEX revision_request_events_revision_request_id_occurred_at_index ON public.revision_request_events USING btree (revision_request_id, occurred_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `revision_requests`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_26_000001_create_document_review_integration_tables.php (create)`, `2026_07_26_000001_create_document_review_integration_tables.php (drop in down/cleanup path)`, `2026_08_11_000001_modernize_revision_requests_schema.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('revision_requests_id_seq'::regclass)` | — |
| 2 | `research_project_id` | `bigint` | Yes | `—` | — |
| 3 | `document_id` | `bigint` | Yes | `—` | — |
| 4 | `requested_by` | `bigint` | No | `—` | — |
| 5 | `assigned_to` | `bigint` | Yes | `—` | — |
| 6 | `title` | `character varying(255)` | No | `—` | — |
| 7 | `instructions` | `text` | No | `—` | — |
| 8 | `status` | `character varying(32)` | No | `'open'::character varying` | — |
| 9 | `due_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 10 | `resolved_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 12 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 13 | `research_class_group_id` | `bigint` | Yes | `—` | — |
| 14 | `source_document_review_id` | `bigint` | Yes | `—` | — |
| 15 | `submitted_document_id` | `bigint` | Yes | `—` | — |
| 16 | `source_type` | `character varying(32)` | No | `'document_review'::character varying` | — |
| 17 | `invalidated_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 18 | `invalidated_reason` | `text` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `revision_requests_assigned_to_foreign` | FOREIGN KEY | `FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE` |
| `revision_requests_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `revision_requests_requested_by_foreign` | FOREIGN KEY | `FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `revision_requests_research_class_group_id_foreign` | FOREIGN KEY | `FOREIGN KEY (research_class_group_id) REFERENCES research_class_groups(id) ON DELETE RESTRICT` |
| `revision_requests_source_document_review_id_foreign` | FOREIGN KEY | `FOREIGN KEY (source_document_review_id) REFERENCES document_reviews(id) ON DELETE SET NULL` |
| `revision_requests_submitted_document_id_foreign` | FOREIGN KEY | `FOREIGN KEY (submitted_document_id) REFERENCES documents(id) ON DELETE SET NULL` |
| `revision_requests_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `revision_requests_source_review_unique` | UNIQUE | `UNIQUE (source_document_review_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `rev_req_due_at_idx` | `CREATE INDEX rev_req_due_at_idx ON public.revision_requests USING btree (due_at)` |
| `rev_req_group_status_idx` | `CREATE INDEX rev_req_group_status_idx ON public.revision_requests USING btree (research_class_group_id, status)` |
| `rev_req_submitted_doc_idx` | `CREATE INDEX rev_req_submitted_doc_idx ON public.revision_requests USING btree (submitted_document_id)` |
| `revision_requests_assigned_to_status_created_at_index` | `CREATE INDEX revision_requests_assigned_to_status_created_at_index ON public.revision_requests USING btree (assigned_to, status, created_at)` |
| `revision_requests_document_id_status_index` | `CREATE INDEX revision_requests_document_id_status_index ON public.revision_requests USING btree (document_id, status)` |
| `revision_requests_pkey` | `CREATE UNIQUE INDEX revision_requests_pkey ON public.revision_requests USING btree (id)` |
| `revision_requests_research_project_id_status_index` | `CREATE INDEX revision_requests_research_project_id_status_index ON public.revision_requests USING btree (research_project_id, status)` |
| `revision_requests_source_review_unique` | `CREATE UNIQUE INDEX revision_requests_source_review_unique ON public.revision_requests USING btree (source_document_review_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `role_has_permissions`

- Current rows: **306**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: No direct `Schema::create`, `Schema::table`, or `Schema::dropIfExists` reference was detected in the repository migration files.

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `permission_id` | `bigint` | No | `—` | — |
| 2 | `role_id` | `bigint` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `role_has_permissions_permission_id_foreign` | FOREIGN KEY | `FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE` |
| `role_has_permissions_role_id_foreign` | FOREIGN KEY | `FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE` |
| `role_has_permissions_pkey` | PRIMARY KEY | `PRIMARY KEY (permission_id, role_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `role_has_permissions_pkey` | `CREATE UNIQUE INDEX role_has_permissions_pkey ON public.role_has_permissions USING btree (permission_id, role_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `roles`

- Current rows: **18**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_05_000003_add_dynamic_rbac_metadata.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('roles_id_seq'::regclass)` | — |
| 2 | `name` | `character varying(255)` | No | `—` | — |
| 3 | `guard_name` | `character varying(255)` | No | `—` | — |
| 4 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 5 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 6 | `display_name` | `character varying(255)` | Yes | `—` | — |
| 7 | `description` | `text` | Yes | `—` | — |
| 8 | `is_system` | `boolean` | No | `false` | — |
| 9 | `is_assignable` | `boolean` | No | `true` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `roles_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `roles_name_guard_name_unique` | UNIQUE | `UNIQUE (name, guard_name)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `roles_is_assignable_index` | `CREATE INDEX roles_is_assignable_index ON public.roles USING btree (is_assignable)` |
| `roles_is_system_index` | `CREATE INDEX roles_is_system_index ON public.roles USING btree (is_system)` |
| `roles_name_guard_name_unique` | `CREATE UNIQUE INDEX roles_name_guard_name_unique ON public.roles USING btree (name, guard_name)` |
| `roles_pkey` | `CREATE UNIQUE INDEX roles_pkey ON public.roles USING btree (id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `sessions`

- Current rows: **0**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000000_create_users_table.php (create)`, `0001_01_01_000000_create_users_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `character varying(255)` | No | `—` | — |
| 2 | `user_id` | `bigint` | Yes | `—` | — |
| 3 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 4 | `user_agent` | `text` | Yes | `—` | — |
| 5 | `payload` | `text` | No | `—` | — |
| 6 | `last_activity` | `integer` | No | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `sessions_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `sessions_last_activity_index` | `CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity)` |
| `sessions_pkey` | `CREATE UNIQUE INDEX sessions_pkey ON public.sessions USING btree (id)` |
| `sessions_user_id_index` | `CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `signature_audits`

- Current rows: **4**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_05_000001_create_user_signatures_tables.php (create)`, `2026_08_05_000001_create_user_signatures_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('signature_audits_id_seq'::regclass)` | — |
| 2 | `user_signature_id` | `bigint` | Yes | `—` | — |
| 3 | `user_id` | `bigint` | Yes | `—` | — |
| 4 | `action` | `character varying(30)` | No | `—` | — |
| 5 | `ip_address` | `character varying(45)` | Yes | `—` | — |
| 6 | `user_agent` | `character varying(1000)` | Yes | `—` | — |
| 7 | `occurred_at` | `timestamp(0) with time zone` | No | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `signature_audits_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL` |
| `signature_audits_user_signature_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_signature_id) REFERENCES user_signatures(id) ON DELETE SET NULL` |
| `signature_audits_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `signature_audits_action_occurred_at_index` | `CREATE INDEX signature_audits_action_occurred_at_index ON public.signature_audits USING btree (action, occurred_at)` |
| `signature_audits_pkey` | `CREATE UNIQUE INDEX signature_audits_pkey ON public.signature_audits USING btree (id)` |
| `signature_audits_user_id_occurred_at_index` | `CREATE INDEX signature_audits_user_id_occurred_at_index ON public.signature_audits USING btree (user_id, occurred_at)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `student_profiles`

- Current rows: **3**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_07_24_000000_create_research_core_tables.php (create)`, `2026_07_24_000000_create_research_core_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('student_profiles_id_seq'::regclass)` | — |
| 2 | `user_id` | `bigint` | No | `—` | — |
| 3 | `program_id` | `bigint` | No | `—` | — |
| 4 | `student_number` | `character varying(50)` | No | `—` | — |
| 5 | `year_level` | `smallint` | Yes | `—` | — |
| 6 | `contact_number` | `character varying(30)` | Yes | `—` | — |
| 7 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 8 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `student_profiles_program_id_foreign` | FOREIGN KEY | `FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT` |
| `student_profiles_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `student_profiles_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `student_profiles_student_number_unique` | UNIQUE | `UNIQUE (student_number)` |
| `student_profiles_user_id_unique` | UNIQUE | `UNIQUE (user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `student_profiles_pkey` | `CREATE UNIQUE INDEX student_profiles_pkey ON public.student_profiles USING btree (id)` |
| `student_profiles_student_number_unique` | `CREATE UNIQUE INDEX student_profiles_student_number_unique ON public.student_profiles USING btree (student_number)` |
| `student_profiles_user_id_unique` | `CREATE UNIQUE INDEX student_profiles_user_id_unique ON public.student_profiles USING btree (user_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `system_settings`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **No**
- RLS forced for owner: **No**
- Migration source references: `2026_08_05_000004_create_system_settings_table.php (create)`, `2026_08_05_000004_create_system_settings_table.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('system_settings_id_seq'::regclass)` | — |
| 2 | `system_name` | `character varying(255)` | No | `'NDMU Research Management and Assistance System'::character varying` | — |
| 3 | `support_email` | `character varying(255)` | No | `'research@ndmu.edu.ph'::character varying` | — |
| 4 | `student_registration_enabled` | `boolean` | No | `true` | — |
| 5 | `email_notifications_enabled` | `boolean` | No | `true` | — |
| 6 | `maintenance_notice` | `character varying(500)` | Yes | `—` | — |
| 7 | `updated_by` | `bigint` | Yes | `—` | — |
| 8 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 9 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `system_settings_updated_by_foreign` | FOREIGN KEY | `FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL` |
| `system_settings_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `system_settings_pkey` | `CREATE UNIQUE INDEX system_settings_pkey ON public.system_settings USING btree (id)` |

#### Row-level security policies

RLS is not enabled and no policy is defined.

#### Triggers

No user-defined trigger reported.

### `title_presentations`

- Current rows: **1**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_22_000001_create_title_presentation_workflow.php (create)`, `2026_08_22_000001_create_title_presentation_workflow.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('title_presentations_id_seq'::regclass)` | — |
| 2 | `defense_id` | `bigint` | No | `—` | — |
| 3 | `official_form_instance_id` | `bigint` | No | `—` | — |
| 4 | `official_form_version_id` | `bigint` | No | `—` | — |
| 5 | `status` | `character varying(40)` | No | `'scheduled'::character varying` | — |
| 6 | `approved_title_number` | `smallint` | Yes | `—` | — |
| 7 | `remarks` | `text` | Yes | `—` | — |
| 8 | `result_recorded_by` | `bigint` | Yes | `—` | — |
| 9 | `result_recorded_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 10 | `presented_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 11 | `presentation_completed_by` | `bigint` | Yes | `—` | — |
| 12 | `finalized_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 13 | `finalized_by` | `bigint` | Yes | `—` | — |
| 14 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 15 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `title_presentations_approved_number_check` | CHECK | `CHECK (approved_title_number IS NULL OR approved_title_number >= 1 AND approved_title_number <= 3)` |
| `title_presentations_defense_id_foreign` | FOREIGN KEY | `FOREIGN KEY (defense_id) REFERENCES defenses(id) ON DELETE RESTRICT` |
| `title_presentations_finalized_by_foreign` | FOREIGN KEY | `FOREIGN KEY (finalized_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `title_presentations_official_form_instance_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_instance_id) REFERENCES official_form_instances(id) ON DELETE RESTRICT` |
| `title_presentations_official_form_version_id_foreign` | FOREIGN KEY | `FOREIGN KEY (official_form_version_id) REFERENCES official_form_versions(id) ON DELETE RESTRICT` |
| `title_presentations_presentation_completed_by_foreign` | FOREIGN KEY | `FOREIGN KEY (presentation_completed_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `title_presentations_result_recorded_by_foreign` | FOREIGN KEY | `FOREIGN KEY (result_recorded_by) REFERENCES users(id) ON DELETE RESTRICT` |
| `title_presentations_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `title_presentations_defense_id_unique` | UNIQUE | `UNIQUE (defense_id)` |
| `title_presentations_official_form_instance_id_unique` | UNIQUE | `UNIQUE (official_form_instance_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `title_presentation_version_status_idx` | `CREATE INDEX title_presentation_version_status_idx ON public.title_presentations USING btree (official_form_version_id, status)` |
| `title_presentations_defense_id_unique` | `CREATE UNIQUE INDEX title_presentations_defense_id_unique ON public.title_presentations USING btree (defense_id)` |
| `title_presentations_official_form_instance_id_unique` | `CREATE UNIQUE INDEX title_presentations_official_form_instance_id_unique ON public.title_presentations USING btree (official_form_instance_id)` |
| `title_presentations_pkey` | `CREATE UNIQUE INDEX title_presentations_pkey ON public.title_presentations USING btree (id)` |
| `title_presentations_status_index` | `CREATE INDEX title_presentations_status_index ON public.title_presentations USING btree (status)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `user_signatures`

- Current rows: **4**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `2026_08_05_000001_create_user_signatures_tables.php (create)`, `2026_08_05_000001_create_user_signatures_tables.php (drop in down/cleanup path)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('user_signatures_id_seq'::regclass)` | — |
| 2 | `user_id` | `bigint` | No | `—` | — |
| 3 | `storage_disk` | `character varying(50)` | No | `—` | — |
| 4 | `storage_path` | `text` | No | `—` | — |
| 5 | `original_filename` | `character varying(255)` | No | `—` | — |
| 6 | `mime_type` | `character varying(50)` | No | `—` | — |
| 7 | `file_size` | `bigint` | No | `—` | — |
| 8 | `content_sha256` | `character(64)` | No | `—` | — |
| 9 | `registered_at` | `timestamp(0) with time zone` | No | `—` | — |
| 10 | `created_at` | `timestamp(0) with time zone` | Yes | `—` | — |
| 11 | `updated_at` | `timestamp(0) with time zone` | Yes | `—` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `user_signatures_user_id_foreign` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE` |
| `user_signatures_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `user_signatures_storage_path_unique` | UNIQUE | `UNIQUE (storage_path)` |
| `user_signatures_user_id_unique` | UNIQUE | `UNIQUE (user_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `user_signatures_content_sha256_index` | `CREATE INDEX user_signatures_content_sha256_index ON public.user_signatures USING btree (content_sha256)` |
| `user_signatures_pkey` | `CREATE UNIQUE INDEX user_signatures_pkey ON public.user_signatures USING btree (id)` |
| `user_signatures_storage_path_unique` | `CREATE UNIQUE INDEX user_signatures_storage_path_unique ON public.user_signatures USING btree (storage_path)` |
| `user_signatures_user_id_registered_at_index` | `CREATE INDEX user_signatures_user_id_registered_at_index ON public.user_signatures USING btree (user_id, registered_at)` |
| `user_signatures_user_id_unique` | `CREATE UNIQUE INDEX user_signatures_user_id_unique ON public.user_signatures USING btree (user_id)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

### `users`

- Current rows: **28**
- Owner: `postgres`
- RLS enabled: **Yes**
- RLS forced for owner: **No**
- Migration source references: `0001_01_01_000000_create_users_table.php (create)`, `0001_01_01_000000_create_users_table.php (drop in down/cleanup path)`, `2026_07_23_124858_add_profile_fields_to_users_table.php (alter)`, `2026_08_05_000003_add_dynamic_rbac_metadata.php (alter)`

#### Columns

| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |
| ---: | --- | --- | --- | --- | --- |
| 1 | `id` | `bigint` | No | `nextval('users_id_seq'::regclass)` | — |
| 2 | `name` | `character varying(255)` | No | `—` | — |
| 3 | `email` | `character varying(255)` | No | `—` | — |
| 4 | `email_verified_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 5 | `password` | `character varying(255)` | No | `—` | — |
| 6 | `status` | `character varying(255)` | No | `'pending'::character varying` | — |
| 7 | `approved_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 8 | `remember_token` | `character varying(100)` | Yes | `—` | — |
| 9 | `created_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 10 | `updated_at` | `timestamp(0) without time zone` | Yes | `—` | — |
| 11 | `student_id` | `character varying(255)` | Yes | `—` | — |
| 12 | `program` | `character varying(255)` | Yes | `—` | — |
| 13 | `year_level` | `character varying(255)` | Yes | `—` | — |
| 14 | `department` | `character varying(255)` | Yes | `—` | — |
| 15 | `user_type` | `character varying(20)` | No | `'faculty'::character varying` | — |

#### Constraints

| Name | Type | Definition |
| --- | --- | --- |
| `users_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| `users_email_unique` | UNIQUE | `UNIQUE (email)` |
| `users_student_id_unique` | UNIQUE | `UNIQUE (student_id)` |

#### Indexes

| Name | Definition |
| --- | --- |
| `users_approved_at_index` | `CREATE INDEX users_approved_at_index ON public.users USING btree (approved_at)` |
| `users_email_unique` | `CREATE UNIQUE INDEX users_email_unique ON public.users USING btree (email)` |
| `users_pkey` | `CREATE UNIQUE INDEX users_pkey ON public.users USING btree (id)` |
| `users_status_index` | `CREATE INDEX users_status_index ON public.users USING btree (status)` |
| `users_student_id_unique` | `CREATE UNIQUE INDEX users_student_id_unique ON public.users USING btree (student_id)` |
| `users_user_type_index` | `CREATE INDEX users_user_type_index ON public.users USING btree (user_type)` |

#### Row-level security policies

**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**

#### Triggers

No user-defined trigger reported.

## Views

No views exist in the `public` schema.

## Public functions

No functions exist in the `public` schema.

## Sequences

| Sequence | Type | Start | Minimum | Maximum | Increment |
| --- | --- | ---: | ---: | ---: | ---: |
| `academic_terms_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `academic_years_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `adviser_assignments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `audit_logs_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `colleges_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `consultation_attendances_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `consultation_audits_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `consultation_records_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `consultation_requests_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `consultation_schedule_proposals_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluation_round_panelists_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluation_round_students_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluation_rounds_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluation_student_scores_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluation_student_summaries_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluation_summaries_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_evaluations_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_panel_assignments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_rooms_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defense_schedules_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `defenses_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `departments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `document_access_audits_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `document_review_audits_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `document_review_comments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `document_reviews_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `document_upload_audits_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `documents_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `faculty_profiles_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `failed_jobs_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `jobs_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `migrations_id_seq` | `integer` | 1 | 1 | 2147483647 | 1 |
| `milestone_definitions_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `milestone_evidences_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `official_form_actor_assignments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `official_form_definitions_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `official_form_instances_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `official_form_signatures_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `official_form_verifications_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `official_form_versions_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `permissions_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `programs_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_actor_assignments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_enrollments_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_group_adviser_histories_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_group_adviser_requests_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_group_member_histories_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_group_members_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_class_groups_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_classes_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_group_members_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_group_milestone_events_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_group_milestones_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_groups_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_projects_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `research_proposals_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `revision_request_events_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `revision_requests_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `roles_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `signature_audits_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `student_profiles_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `system_settings_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `title_presentations_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `user_signatures_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |
| `users_id_seq` | `bigint` | 1 | 1 | 9223372036854775807 | 1 |

## Installed PostgreSQL extensions

| Extension | Version |
| --- | --- |
| `plpgsql` | `1.0` |

## Table privilege summary

| Grantee | Privilege | Number of public tables |
| --- | --- | ---: |
| `postgres` | `DELETE` | 74 |
| `postgres` | `INSERT` | 74 |
| `postgres` | `REFERENCES` | 74 |
| `postgres` | `SELECT` | 74 |
| `postgres` | `TRIGGER` | 74 |
| `postgres` | `TRUNCATE` | 74 |
| `postgres` | `UPDATE` | 74 |

## Migration verification

| Migration | Batch | Source file present |
| --- | ---: | --- |
| `0001_01_01_000000_create_users_table` | 1 | Yes |
| `0001_01_01_000001_create_cache_table` | 1 | Yes |
| `0001_01_01_000002_create_jobs_table` | 1 | Yes |
| `2026_07_21_054833_create_permission_tables` | 1 | Yes |
| `2026_07_23_124858_add_profile_fields_to_users_table` | 1 | Yes |
| `2026_07_24_000000_create_research_core_tables` | 1 | Yes |
| `2026_07_24_000001_create_documents_table` | 1 | Yes |
| `2026_07_24_000002_create_document_upload_audits_table` | 1 | Yes |
| `2026_07_24_000003_create_consultation_requests_table` | 1 | Yes |
| `2026_07_25_000001_create_research_classes_table` | 1 | Yes |
| `2026_07_25_000002_create_research_class_enrollments_table` | 1 | Yes |
| `2026_07_25_000003_add_join_request_workflow_to_research_class_enrollments` | 1 | Yes |
| `2026_07_25_000004_create_document_review_tables` | 1 | Yes |
| `2026_07_26_000001_create_document_review_integration_tables` | 1 | Yes |
| `2026_07_27_000001_add_revision_workflow_tables` | 1 | Yes |
| `2026_08_03_000001_harden_public_schema_access` | 1 | Yes |
| `2026_08_04_000001_refactor_classes_for_facilitator_grouping` | 1 | Yes |
| `2026_08_05_000001_create_user_signatures_tables` | 1 | Yes |
| `2026_08_05_000002_create_research_milestones_table` | 1 | Yes |
| `2026_08_05_000003_add_dynamic_rbac_metadata` | 1 | Yes |
| `2026_08_05_000004_create_system_settings_table` | 1 | Yes |
| `2026_08_08_000001_allow_research_class_join_request_history` | 1 | Yes |
| `2026_08_08_000001_create_phase12_adviser_requests_and_group_history_tables` | 1 | Yes |
| `2026_08_08_000002_add_group_leader_and_document_ownership_fields` | 1 | Yes |
| `2026_08_09_000001_create_phase14_repository_foundation` | 1 | Yes |
| `2026_08_09_000002_add_correction_fields_to_document_reviews_table` | 1 | Yes |
| `2026_08_09_000003_create_phase16_consultation_records_tables` | 1 | Yes |
| `2026_08_10_000001_create_audit_logs_table` | 1 | Yes |
| `2026_08_10_000002_add_subject_snapshots_to_audit_logs_table` | 1 | Yes |
| `2026_08_11_000001_modernize_revision_requests_schema` | 1 | Yes |
| `2026_08_11_000002_create_group_owned_research_progress_tables` | 1 | Yes |
| `2026_08_11_000003_enable_rls_on_research_progress_tables` | 1 | Yes |
| `2026_08_11_000004_align_research_milestone_definitions` | 1 | Yes |
| `2026_08_12_000001_create_official_form_catalog_tables` | 1 | Yes |
| `2026_08_12_000002_enable_rls_on_official_form_tables` | 1 | Yes |
| `2026_08_13_000001_create_research_class_actor_assignments_table` | 1 | Yes |
| `2026_08_13_000002_remove_unverified_official_form_permissions` | 1 | Yes |
| `2026_08_14_000001_create_official_form_signatures_tables` | 1 | Yes |
| `2026_08_15_000001_create_defense_rooms_table` | 1 | Yes |
| `2026_08_15_000002_create_defenses_table` | 1 | Yes |
| `2026_08_15_000003_create_defense_schedules_table` | 1 | Yes |
| `2026_08_15_000004_add_current_schedule_id_to_defenses_table` | 1 | Yes |
| `2026_08_15_000005_create_defense_panel_assignments_table` | 1 | Yes |
| `2026_08_15_000006_add_source_snapshot_to_official_form_versions_table` | 1 | Yes |
| `2026_08_15_000007_enable_rls_on_defense_tables` | 1 | Yes |
| `2026_08_16_000001_create_defense_evaluation_tables` | 1 | Yes |
| `2026_08_16_000002_add_completed_fields_to_defenses_table` | 1 | Yes |
| `2026_08_16_000003_add_evaluation_link_to_official_form_instances` | 1 | Yes |
| `2026_08_16_000004_enable_rls_on_evaluation_tables` | 1 | Yes |
| `2026_08_22_000001_create_title_presentation_workflow` | 2 | Yes |

### Migration files not present in the live migration ledger

None. Every migration file in `database/migrations` is present in the live ledger.

## Interpretation and maintenance rules

- Foreign-key behavior, nullability, defaults, unique rules, and checks must be taken from the PostgreSQL definitions above—not inferred from UI labels.
- RLS enabled without an explicit policy is recorded exactly as PostgreSQL reports it. Application authorization still belongs to Laravel middleware, Spatie permissions, policies, and record-scoped queries.
- Laravel framework infrastructure tables such as cache, sessions, queues, notifications, migrations, password resets, and Spatie RBAC tables are included because they physically exist in the active schema.
- Regenerate this file after applying a migration: `php scripts/generate-database-reference.php`.
- Review the resulting Git diff before committing because row counts and the generation timestamp are expected to change.
