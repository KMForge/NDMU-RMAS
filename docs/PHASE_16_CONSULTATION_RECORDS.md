# Phase 16 — Consultation Records Documentation

## Overview

Phase 16 implements an auditable, group-owned **Consultation Records** workflow in NDMU-RMAS. It enables active members of a `ResearchClassGroup` to request academic consultations with their assigned thesis adviser, allows advisers to approve, reject, reschedule, or complete consultations, records attendee participation and discussion notes, supports controlled corrections of completed records, and provides read-only supervision monitoring for research facilitators.

---

## Technical Architecture & Database Design

### Migration File
- `database/migrations/2026_08_09_000003_create_phase16_consultation_records_tables.php`

### Database Tables & Relations

1. **`consultation_requests`** (Modernized Schema)
   - `id` (Primary Key)
   - `research_class_group_id` (foreign key to `research_class_groups`, nullOnDelete)
   - `assigned_adviser_id` (foreign key to `users`, adviser at request creation time)
   - `requested_by` (foreign key to `users`, student who requested the consultation)
   - `request_token` (UUID, idempotency token)
   - `preferred_at` (timestampTz, original requested start time)
   - `confirmed_start_at` (timestampTz, actual scheduled start time)
   - `confirmed_end_at` (timestampTz, actual scheduled end time)
   - `duration_minutes` (integer, default 60)
   - `consultation_mode` (`in_person` | `online`)
   - `location` (text, for in-person)
   - `meeting_url` (text, for online)
   - `document_stage` (string, `App\Enums\DocumentStage`)
   - `document_id` (foreign key to `documents`)
   - `agenda` (text)
   - `status` (`pending` | `reschedule_proposed` | `approved` | `rejected` | `cancelled` | `completed`)
   - `reviewed_by`, `reviewed_at`, `review_notes`
   - `cancelled_by`, `cancelled_at`, `cancellation_reason`
   - **Indexes**: `['assigned_adviser_id', 'status', 'confirmed_start_at', 'confirmed_end_at']`, `['research_class_group_id', 'status']`, `unique(['requested_by', 'request_token'])`.

2. **`consultation_schedule_proposals`** (Proposal History Preservation)
   - `id` (Primary Key)
   - `consultation_request_id` (foreign key to `consultation_requests`, cascadeOnDelete)
   - `proposed_by` (foreign key to `users`, adviser)
   - `proposed_start_at` (timestampTz)
   - `duration_minutes` (integer)
   - `reason` (text, nullable)
   - `status` (`pending_response` | `accepted` | `declined`)
   - `responded_by` (foreign key to `users`, student)
   - `responded_at` (timestampTz)

3. **`consultation_records`** (Completed Consultation Outcomes)
   - `id` (Primary Key)
   - `consultation_request_id` (foreign key to `consultation_requests`, cascadeOnDelete)
   - `research_class_group_id` (foreign key to `research_class_groups`, cascadeOnDelete)
   - `conducted_by` (foreign key to `users`, adviser)
   - `consulted_at` (timestampTz)
   - `duration_minutes` (integer)
   - `consultation_mode` (`in_person` | `online`)
   - `location`, `meeting_url`, `agenda`, `discussion`, `recommendations`, `next_consultation_at`
   - `supersedes_record_id` (foreign key to `consultation_records`, nullOnDelete)
   - `is_superseded` (boolean, default false)
   - `correction_reason` (text, nullable)

4. **`consultation_attendances`** (Student Attendee FK Identity)
   - `id` (Primary Key)
   - `consultation_record_id` (foreign key to `consultation_records`, cascadeOnDelete)
   - `student_id` (foreign key to `users.id`)
   - `attended` (boolean, default true)
   - **Constraint**: `unique(['consultation_record_id', 'student_id'])`

5. **`consultation_audits`** (Feature Audit Logging)
   - `id`, `consultation_request_id`, `research_class_group_id`, `actor_id`, `action`, `status`, `ip_address`, `occurred_at`, `metadata`.

---

## Key Business & Authorization Rules

1. **Group Ownership & Requester Controls**:
   - All active members of an active `ResearchClassGroup` can view their group's consultation requests and completed records.
   - Only the student who originally created the request (`requested_by`) can cancel or respond to reschedule proposals.

2. **Adviser Selection & Booking Window**:
   - The thesis adviser is derived automatically from `ResearchClassGroup.adviser_id`. If no adviser is assigned, request is denied.
   - Advance notice rules: min 6 hours, max 90 days.
   - Allowed durations: 30, 45, 60 minutes (default 60).
   - Only 1 active unresolved request (`pending` or `reschedule_proposed`) is allowed per group at a time.

3. **Double-Booking Prevention under Concurrency**:
   - Schedule approval and reschedule acceptances execute inside a database transaction with `lockForUpdate()` on adviser schedule queries.
   - Prevents overlapping approved schedules for the same adviser using interval logic: `new_start < existing_end AND new_end > existing_start`.

4. **Reschedule Proposal History**:
   - All reschedule iterations (`Schedule A -> declined -> Schedule B -> accepted`) are preserved in `consultation_schedule_proposals`.

5. **Completed Records & Controlled Corrections**:
   - Completed consultations record actual discussion notes, recommendations, and student attendance (`student_id -> users.id`).
   - Controlled record corrections mark the original record as `is_superseded = true`, create a new record referencing `supersedes_record_id`, and record a mandatory `correction_reason`.

6. **Facilitator Read-Only Supervision**:
   - Research facilitators have read-only visibility over consultation requests and records for groups in their owned classes (`ResearchClass.facilitator_id === $user->id`). Facilitators cannot execute adviser decisions.

7. **Zero Side Effects**:
   - Consultation completion does not trigger revision requests, milestone progress updates, document review decisions, or notifications.

---

## Verification Results

- **Feature Test Suite**: `tests/Feature/Consultations/ConsultationWorkflowTest.php`
  - **15 tests executed, 55 assertions, 0 failures**.
- **Full Modern Module Suite**: `tests/Feature/Classes tests/Feature/Documents tests/Feature/Consultations`
  - **131 tests executed, 108 passed, 23 skipped (un-rebuilt phase stubs returning HTTP 410), 510 assertions, 0 failures**.
- **Pint Formatting**: PASSED (`vendor/bin/pint --test`).
- **Vite Build**: PASSED (`npm run build` completed in 1.39s).
