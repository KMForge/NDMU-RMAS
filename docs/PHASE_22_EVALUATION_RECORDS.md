# Phase 22 — Evaluation Records

## Overview
Phase 22 implements the formal Evaluation Records domain module for NDMU-RMAS. It manages the full lifecycle of thesis and capstone defense evaluation rounds, panelist scoring (RES-036), authoritative grade summary calculations (RES-037), digital signature integration, result release, defense completion, and cross-module scheduling guard rails.

---

## Technical Architecture

### 1. Database Schema
- `defense_evaluation_rounds`: Tracks evaluation rounds (`open`, `in_progress`, `complete`, `finalized`, `released`). Stores frozen roster snapshots and summary signer designation.
- `defense_evaluation_round_panelists`: Frozen snapshot of 3 faculty panelists for the round (`position` 1..3, `defense_panel_assignment_id`).
- `defense_evaluation_round_students`: Frozen snapshot of group student members at round opening (`student_name_snapshot`, `group_member_id`).
- `defense_evaluations`: Immutable individual panelist score sheets (RES-036).
- `defense_evaluation_student_scores`: Individual student presentation score components per panelist.
- `defense_evaluation_summaries`: Authoritative round averages for research paper quality (RES-037).
- `defense_evaluation_student_summaries`: Authoritative individual student presentation averages.
- `defenses` table extension: Added `completed_at` (timestamp) and `completed_by` (foreign key to users).
- `official_form_instances` table extension: Added `defense_evaluation_id` (foreign key to defense_evaluations).

### 2. Weighted Scoring Formulas
Scores are strictly calculated server-side using the institutional weighted scoring matrix:
- **Research Paper Total (per Panelist)**:
  $$\text{Paper Total} = \text{round}((\text{Quality} \times 0.50) + (\text{Originality} \times 0.25) + (\text{Relevance} \times 0.25), 2)$$
- **Student Presentation Total (per Panelist per Student)**:
  $$\text{Presentation Total} = \text{round}((\text{Communication} \times 0.20) + (\text{Organization} \times 0.30) + (\text{Effectiveness} \times 0.50), 2)$$
- **Authoritative Research Paper Average**:
  $$\text{Paper Average} = \text{round}\left(\frac{\sum \text{Paper Total}}{3}, 2\right)$$
- **Authoritative Student Presentation Average**:
  $$\text{Presentation Average} = \text{round}\left(\frac{\sum \text{Presentation Total}}{3}, 2\right)$$

### 3. Immutability, Authorization & Fail-Closed Protections
- **Centralized In-Transaction Authorization**: Unified via `EvaluationAuthorization` service, enforcing active faculty account status, verified email, approved profile, research class facilitator ownership, frozen panelist roster membership, and permission check without canonical role dependencies (`$actor->can(...)`).
- **Submission Immutability**: Submitted evaluations (`DefenseEvaluation`) are strictly immutable and cannot be updated or superseded in Phase 22.
- **Fail-Closed Payload Integrity & IDOR Protection**:
  - Overposting protection: System-controlled fields (`research_paper_total`, `presentation_total`, `status`, `submitted_at`, `summary_signer_user_id`, etc.) passed in request payloads are rejected.
  - Student IDOR protection: Scores for unknown/unassigned student IDs are rejected.
  - Exact roster enforcement: Evaluation submission requires scores for every member of the frozen student roster.
- **Strict Student Privacy**: Students can view ONLY released evaluation rounds. Students see ONLY the group Research Paper average and their OWN Presentation average. Panelist identity, individual panelist scores, and peer student scores are strictly hidden from students.
- **Phase 20 Digital Signature Gate**: Round finalization is strictly gated on the designated panelist signing the generated RES-037 official form instance via Phase 20 digital signature architecture (`academic_action = 'sign'`).
- **Phase 21 Scheduling Guard Rails**: Defenses cannot be rescheduled or cancelled, and panel rosters cannot be modified once an evaluation round exists.

---

## Evaluation Lifecycle & State Transitions

1. **Open Round (`open`)**
   - Initiated by Facilitator owning the research class.
   - Defense must be `scheduled`. Exactly 3 active faculty panelists must be assigned.
   - Snapshots panelists and student group members. Designated summary signer is set.

2. **Save Draft / Submit (`in_progress` -> `complete`)**
   - Panelist saves draft or submits scores (RES-036).
   - Generates RES-036 form version snapshot containing paper criteria, comments, recommendations, and complete student presentation score rows.
   - Once all 3 panelists submit, round status automatically advances to `complete`, `all_submitted_at` timestamp is set, authoritative averages are calculated into `DefenseEvaluationSummary` / `DefenseEvaluationStudentSummary`, and the official RES-037 form instance is generated containing individual panelist totals (P1, P2, P3) and round averages.

3. **Sign & Finalize (`finalized`)**
   - Designated summary signer signs RES-037 using Phase 20 digital signature workflow.
   - Upon valid signature application, `FinalizeDefenseEvaluationRound` executes, transitioning round status to `finalized` and recording `finalized_at`.

4. **Release Results (`released`)**
   - Facilitator releases finalized evaluation results to students and advisers (`evaluations.release`).
   - Round status transitions to `released`, setting `released_at` and `released_by`.

5. **Complete Defense (`completed`)**
   - Facilitator marks the defense as `completed`.
   - `defenses.status` transitions to `completed`, setting `completed_at` and `completed_by`.

---

## Verification & Test Matrix

- `tests/Feature/Evaluations/DefenseEvaluationTest.php`: Complete end-to-end evaluation flow, calculations, scoring bounds, round completion, RES-037 generation, digital signature finalization, release, and defense completion.
- `tests/Feature/Evaluations/DefenseEvaluationSecurityTest.php`: Submission immutability, RBAC authorization, strict student privacy, fail-closed student IDOR protections, overposting guards, custom role faculty evaluation, signer permission differentiation, admin academic evaluation denial, and Phase 21 scheduling guard rails.
