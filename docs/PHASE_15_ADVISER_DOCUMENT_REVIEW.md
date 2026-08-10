# NDMU-RMAS Phase 15 — Adviser Document Review

## Overview

Phase 15 implements an auditable, group-owned **Adviser Document Review** workflow in NDMU-RMAS. It enables assigned thesis advisers to review current document submissions across the five official document stages defined in `App\Enums\DocumentStage`:
1. Title Proposal (`title_proposal`)
2. Proposal Defense (`proposal_defense`)
3. Pre-Final Defense (`pre_final_defense`)
4. Final Defense (`final_defense`)
5. Final Manuscript (`final_manuscript`)

Advisers can record severity-graded findings (`comment`, `revision`, `critical`), resolve findings, enforce acceptance blocking rules, record authoritative review decisions (`accepted`, `revision_requested`, `rejected`), and execute controlled decision corrections while preserving immutable review history evidence.

---

## Architectural & Authorization Rules

1. **Academic Review Queue & Decision Scope**:
   - Academic decision authority requires the **current assigned adviser** (`ResearchClassGroup.adviser_id === $user->id`) of an active, non-disbanded `ResearchClassGroup` holding `documents.review` permission.
   - Broad repository permissions (`research.view-all`, `documents.download-any`, admin, facilitator, dean) allow document repository viewing/downloading under Phase 14 rules, but do **NOT** broaden the Adviser Document Review Queue scope nor grant review mutation rights.
   - Review queue queries use `DocumentReviewerAccess::scopeForReviewQueue()` to strictly enforce adviser assignment scoping.

2. **Legacy & Null-Group Protection**:
   - Documents with `research_class_group_id === null` remain read-only historical records and do NOT enter active Phase 15 adviser review mutation scope.

3. **VERSIONING & VOID Protection**:
   - Review mutations (comments, resolutions, decisions, corrections) are strictly restricted to `CURRENT` document versions (`is_current === true`). `VOID` versions are read-only.

4. **Acceptance Blocking Rule**:
   - Server-side check inside `ReviewDocument` and `CorrectDocumentReviewDecision` blocks marking a document as `accepted` if it contains unresolved findings of `revision` or `critical` severity. Informational `comment` severity findings do not block acceptance.

5. **File Type Annotations**:
   - **PDF**: Supports optional page-number annotations (positive integer between 1 and 10000).
   - **DOCX**: Findings are supported, but page-number annotations are strictly prohibited. Requests submitting a page number for a DOCX file are rejected with HTTP 422 Unprocessable Entity.

6. **Finding Threading & Post-Decision Rules**:
   - Phase 15 uses flat formal findings (user-controlled `parent_id` input is disabled).
   - Once a document receives a final review decision (`accepted`, `revision_requested`, `rejected`), new findings cannot be posted on that version unless decision correction is performed.

7. **Controlled Decision Correction**:
   - Final review decisions are historical evidence and are never silently overwritten or deleted.
   - Correcting a decision marks the original `DocumentReview` as `is_superseded = true` and creates a new `DocumentReview` record referencing `supersedes_review_id` with a mandatory `correction_reason`.
   - Sequential corrections form an unbroken immutable audit chain (`Review 1 -> Review 2 -> Review 3`). Only the latest effective review record has `is_superseded = false`.

8. **Phase Boundary & Zero Side-Effects**:
   - Review decisions do NOT automatically create Phase 17 `RevisionRequest` or Phase 18 progress milestone updates, nor do they mutate `ResearchProposal` or dispatch notifications (Phase 23). Review activity is logged to `document_review_audits`.

---

## Technical Stack & Database Schema

- **Models**:
  - `Document` (`app/Models/Document.php`)
  - `DocumentReview` (`app/Models/DocumentReview.php`): Stores `supersedes_review_id`, `is_superseded`, `decision`, `review_notes`, `correction_reason`, `reviewed_at`.
  - `DocumentReviewComment` (`app/Models/DocumentReviewComment.php`): Stores `severity` (`comment`, `revision`, `critical`), `page_number`, `comment`, `resolved_by`, `resolved_at`.
  - `DocumentReviewAudit` (`app/Models/DocumentReviewAudit.php`): Records audit events (`comment_added`, `comment_resolved`, `review_decision_recorded`, `review_decision_corrected`).
- **Support & Actions**:
  - `DocumentReviewerAccess.php` (provides `canReview()` and `scopeForReviewQueue()`)
  - `ReviewDocument.php`
  - `CorrectDocumentReviewDecision.php`
  - `AddDocumentReviewComment.php`
  - `ResolveDocumentReviewComment.php`
  - `GetAdviserDocumentReviewData.php`

---

## Routes & Middleware

- `POST /adviser/documents/{document}/comments` → `DocumentReviewController@comment`
- `PATCH /adviser/documents/{document}/comments/{comment}/resolve` → `DocumentReviewController@resolve`
- `PATCH /adviser/documents/{document}/review` → `DocumentReviewController@review`
- `PATCH /adviser/documents/{document}/review/correct` → `DocumentReviewController@correct`

Middleware stack: `auth`, `verified`, `active`, `permission:documents.review`, `throttle:document-reviews`.

---

## Verification

- **Phase 15 Feature Test Suite**: `tests/Feature/Documents/AdviserDocumentReviewTest.php`
  - **23 tests executed, 93 assertions, 0 failures**.
- **Phase 10–15 Regression Suite**: `tests/Feature/Classes tests/Feature/Documents`
  - **114 tests executed, 93 passed, 21 skipped (un-rebuilt phase stubs), 0 failures**.
- **Formatting**: `vendor/bin/pint --test` → **PASSED**.
- **Production Asset Build**: `npm run build` → **PASSED** (Vite built in 1.61s).
