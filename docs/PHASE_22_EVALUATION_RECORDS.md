# Phase 22 — Evaluation Records

## Status

- **Phase status:** Completed
- **Current evidence baseline:** `8b15011507c76d76c221e8be36e9a204fbd67a03`

Phase 22 has no unresolved technical or cross-phase dependency. Phase 20 signature finalization and Phase 21 defense/schedule/panel context are integrated and tested. The scoring, roster, signer, release, completion, and immutability rules below are implemented project design rules; formal institutional confirmation remains a non-blocking policy item and is not misclassified as missing code.

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
Scores are strictly calculated server-side using the implemented project scoring matrix:
- **Research Paper Total (per Panelist)**:
  $$\text{Paper Total} = \text{round}((\text{Quality} \times 0.50) + (\text{Originality} \times 0.25) + (\text{Relevance} \times 0.25), 2)$$
- **Student Presentation Total (per Panelist per Student)**:
  $$\text{Presentation Total} = \text{round}((\text{Communication} \times 0.20) + (\text{Organization} \times 0.30) + (\text{Effectiveness} \times 0.50), 2)$$
- **Authoritative Research Paper Average**:
  $$\text{Paper Average} = \text{round}\left(\frac{\sum \text{Paper Total}}{3}, 2\right)$$
- **Authoritative Student Presentation Average**:
  $$\text{Presentation Average} = \text{round}\left(\frac{\sum \text{Presentation Total}}{3}, 2\right)$$

### 3. Pre-Freeze Panel Candidate & Summary Signer Eligibility Rules
Before an evaluation round is created or frozen in database transaction:
- **Panel Candidate Eligibility**: Every single one of the exactly 3 assigned panelists must independently satisfy:
  - `user_type === UserType::Faculty`
  - `status === AccountStatus::Active`
  - `approved_at !== null`
  - `email_verified_at !== null`
  - `evaluations.create` permission
  - `forms.res-036.evaluate` permission
  If any assigned panelist fails any requirement, the transaction aborts and rolls back completely (no partial round or frozen roster is created).
- **Summary Signer Eligibility & Default Signer Logic**:
  - The summary signer must be one of the 3 frozen panel candidates and must additionally hold `forms.res-037.sign` permission.
  - Non-signing panelists do **not** require `forms.res-037.sign` to submit their RES-036 evaluations.
  - If a designated summary signer user ID is provided, the system verifies candidate eligibility and `forms.res-037.sign` permission. If invalid, the transaction aborts.
  - If no summary signer user ID is provided, the system deterministically selects the first candidate among the 3 frozen panelists who holds `forms.res-037.sign`. If no assigned panelist holds `forms.res-037.sign`, round creation is denied with an explicit validation error.

### 4. Immutability, Authorization & Fail-Closed Protections
- **Centralized In-Transaction Authorization**: Unified via `EvaluationAuthorization` service (`assertEligiblePanelCandidate`, `assertSummarySignerCandidate`, `assertFacilitatorOwnsDefense`, `assertEligiblePanelist`, `assertSummarySigner`).
- **Submission Immutability**: Submitted evaluations (`DefenseEvaluation`) are strictly immutable and cannot be updated or superseded in Phase 22.
- **Fail-Closed Payload Integrity & IDOR Protection**:
  - Overposting protection: System-controlled fields (`research_paper_total`, `presentation_total`, `status`, `submitted_at`, `summary_signer_user_id`, etc.) passed in request payloads are rejected.
  - Student IDOR protection: Scores for unknown/unassigned student IDs are rejected.
  - Exact roster enforcement: Evaluation submission requires scores for every member of the frozen student roster.
- **Strict Student Privacy**: Students can view ONLY released evaluation rounds. Students see ONLY the group Research Paper average and their OWN Presentation average. Panelist identity, individual panelist scores, and peer student scores are strictly hidden from students.
- **Phase 20 Digital Signature Gate**: Round finalization is strictly gated on the designated panelist signing the generated RES-037 official form instance via Phase 20 digital signature architecture (`academic_action = 'sign'`).
- **Phase 21 Scheduling Guard Rails**: Defenses cannot be rescheduled or cancelled, and panel rosters cannot be modified once an evaluation round exists.

### 5. Project Design Rules Context
The following business rules operate as verified project design rules for NDMU-RMAS:
- Exactly 3 active faculty panel assignments required for round opening.
- Weighted score distributions: 50% Quality / 25% Originality / 25% Relevance and 20% Communication / 30% Organization / 50% Effectiveness.
- Single designated Summary Signatory model for RES-037 summary attestation.
- Facilitator-owned result release and defense completion triggers.

These rules are authoritative for the current application behavior because they are encoded in migrations, actions, authorization, and tests. The repository does not contain separate evidence that NDMU has formally ratified each formula and lifecycle choice as institutional policy. No formula or lifecycle is changed during this audit.

## Dependency table

| Dependency | Type | Evidence | Current status | Required owner/action |
| --- | --- | --- | --- | --- |
| Phase 20 RES-037 digital signature | Cross-phase | `ApplyOfficialFormSignature` invokes `FinalizeDefenseEvaluationRound`; `OfficialFormSignatureTest` and evaluation security tests | Resolved | None |
| Phase 21 defense, schedule, and panel assignment | Cross-phase | `OpenDefenseEvaluationRound` locks the defense and snapshots the current schedule, three active assignments, and group students | Resolved | None |
| Frozen panel and student rosters | Cross-phase / technical | Evaluation-round snapshot tables and `OpenDefenseEvaluationRound`; Phase 22 tests | Resolved | None |
| Result release | Technical | `ReleaseDefenseEvaluationResults` requires finalized RES-037 with a verified signature and records release metadata | Resolved | None |
| Defense completion | Cross-phase | `CompleteDefenseAfterEvaluation` requires a released round and writes `completed_at`/`completed_by` | Resolved | None |
| Summary signer authorization | Cross-phase / technical | `EvaluationAuthorization`, `OpenDefenseEvaluationRound`, `DesignateEvaluationSummarySigner`, signature finalization tests | Resolved | None |
| Exactly three panelists | Institutional policy | Enforced and tested project design rule | Implemented; formal confirmation pending | NDMU Research Office should confirm panel cardinality and substitution policy |
| One designated RES-037 signer | Institutional policy | Enforced and tested project design rule | Implemented; formal confirmation pending | Confirm whether one signatory is sufficient and how that person is selected |
| Paper weights 50/25/25 | Institutional policy | Server-side calculation and tests | Implemented; formal confirmation pending | Confirm official rubric and rounding rule |
| Presentation weights 20/30/50 | Institutional policy | Server-side calculation and tests | Implemented; formal confirmation pending | Confirm official rubric and rounding rule |
| Facilitator release and completion authority | Institutional policy | Permission and exact class-ownership checks in dedicated actions | Implemented; formal confirmation pending | Confirm whether additional approval is required |
| Immutable submitted evaluations | Institutional policy | Update/supersede attempts fail closed in Phase 22 security tests | Implemented; formal confirmation pending | Confirm whether a future correction/superseding process is permitted |

---

## Evaluation Lifecycle & State Transitions

1. **Open Round (`open`)**
   - Initiated by Facilitator owning the research class.
   - Defense must be `scheduled`. Exactly 3 active faculty panelists must be assigned and pass candidate eligibility.
   - Snapshots panelists and student group members. Designated summary signer is validated and stored.

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

## Final Closure Verification Evidence

### Verification Matrix Summary

| Suite / Test Group | Command | Total | Passed | Failed | Skipped | Assertions |
| --- | --- | --- | --- | --- | --- | --- |
| **Focused Phase 22** | `php artisan test --filter DefenseEvaluation` | 26 | 26 | 0 | 0 | 84 |
| **Phase 21 Scheduling** | `php artisan test tests/Feature/DefenseSchedulingTest.php ...` | 27 | 27 | 0 | 0 | 65 |
| **Official Forms** | `php artisan test tests/Feature/OfficialForms/` | 78 | 78 | 0 | 0 | 379 |
| **Signatures** | `php artisan test tests/Feature/Signatures/` | 11 | 10 | 0 | 1 | 55 |
| **Research Progress** | `php artisan test tests/Feature/ResearchProgress/` | 20 | 20 | 0 | 0 | 94 |
| **Dashboard Views** | `php artisan test --filter Dashboard` | 52 | 49 | 0 | 3 | 289 |
| **Full Application Suite** | `php artisan test` | 393 | 369 | 0 | 24 | 1,617 |

### Quality Gates Execution

- **Pint Code Style**: `vendor/bin/pint --test` — **PASS**
- **Vite Production Build**: `npm run build` — **PASS** (built in 1.32s)
- **Blade Template Cache**: `php artisan view:clear; php artisan view:cache` — **PASS**
- **Database Migrations**: `php artisan migrate:status` — **PASS** (49/49 ran)
- **Git Diff & Whitespace Check**: `git diff --check` — **PASS** (Clean)

## Current dependency-correction verification

| Command | Result |
| --- | --- |
| `php artisan test --filter=DefenseEvaluation` | PASS — 26 tests, 84 assertions, 0 failures |
| `php artisan test --filter=Defense` | PASS — 66 tests, 202 assertions, 0 failures |
| `php artisan test tests/Feature/OfficialForms/` | PASS — 78 tests, 382 assertions, 0 failures |
| `php artisan test --filter=Signature` | PASS — 30 total; 29 passed, 1 skipped; 128 assertions, 0 failures |

No Phase 22 application code or test was changed during this dependency-correction pass.
