# Phase 22 — Evaluation Records

## Overview
Phase 22 implements the formal Evaluation Records domain module for NDMU-RMAS. It manages the full lifecycle of thesis and capstone defense evaluation rounds, panelist scoring (RES-036), authoritative grade summary calculations (RES-037), digital signature integration, result release, defense completion, and cross-module scheduling guard rails.

---

## Technical Architecture

### 1. Database Schema
- `defense_evaluation_rounds`: Tracks evaluation rounds (`open`, `in_progress`, `complete`, `finalized`, `released`). Stores frozen roster snapshots and summary signer designation.
- `defense_evaluation_round_panelists`: Frozen snapshot of 3 faculty panelists for the round.
- `defense_evaluation_round_students`: Frozen snapshot of group student members at round opening.
- `defense_evaluations`: Immutable individual panelist score sheets (RES-036).
- `defense_evaluation_student_scores`: Individual student presentation score components per panelist.
- `defense_evaluation_summaries`: Authoritative round averages for research paper quality (RES-037).
- `defense_evaluation_student_summaries`: Authoritative individual student presentation averages.
- `defenses` table extension: Added `completed_at` (timestamp) and `completed_by` (foreign key to users).
- `official_form_instances` table extension: Added `defense_evaluation_id` (foreign key to defense_evaluations).

### 2. Immutability & Security Rules
- **Submission Immutability**: Submitted evaluations (`DefenseEvaluation`) are strictly immutable and cannot be updated or superseded in Phase 22.
- **Server-Side Scoring**: Scores are strictly calculated server-side. Research paper total = `(quality + originality + relevance) / 3`. Student presentation total = `(communication + organization + effectiveness) / 3`.
- **Strict Student Privacy**: Students can view ONLY released evaluation rounds. Students see ONLY the group Research Paper average and their OWN Presentation average. Panelist identity, individual panelist scores, and peer student scores are strictly hidden from students.
- **Phase 20 Digital Signature Gate**: Round finalization is strictly gated on the designated panelist signing the generated RES-037 official form instance via Phase 20 digital signature architecture.
- **Phase 21 Scheduling Guard Rails**: Defenses cannot be rescheduled or cancelled, and panel rosters cannot be modified once an evaluation round exists.

---

## Evaluation Lifecycle & State Transitions

1. **Open Round (`open`)**
   - Initiated by Facilitator owning the research class.
   - Defense must be `scheduled`. Exactly 3 active faculty panelists must be assigned.
   - Snapshots panelists and student group members. Designated summary signer is set.

2. **Save Draft / Submit (`in_progress` -> `complete`)**
   - Panelist saves draft or submits scores (RES-036).
   - Once all 3 panelists submit, round status automatically advances to `complete`, `all_submitted_at` timestamp is set, authoritative averages are calculated into `DefenseEvaluationSummary` / `DefenseEvaluationStudentSummary`, and the official RES-037 form instance is generated for the round.

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

## Verification & Test Coverage
- `tests/Feature/Evaluations/DefenseEvaluationTest.php`: Complete end-to-end evaluation flow, calculations, scoring bounds, round completion, RES-037 generation, digital signature finalization, release, and defense completion.
- `tests/Feature/Evaluations/DefenseEvaluationSecurityTest.php`: Submission immutability, RBAC authorization, strict student privacy, and Phase 21 scheduling guard rails.
