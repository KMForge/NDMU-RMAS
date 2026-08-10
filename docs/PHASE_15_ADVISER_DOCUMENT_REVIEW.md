# NDMU-RMAS Phase 15 — Adviser Document Review

## Overview

Phase 15 implements an auditable, group-owned **Adviser Document Review** workflow in NDMU-RMAS. It enables assigned thesis advisers to review current document submissions across all five official document stages (Proposal Defense, Ethics Review, Progress Monitoring, Final Defense, and Final Archiving), record severity-graded findings (`comment`, `revision`, `critical`), resolve findings, enforce acceptance blocking rules, record authoritative review decisions (`accepted`, `revision_requested`, `rejected`), and make controlled decision corrections while preserving original evidence.

---

## Architectural & Authorization Rules

1. **Current Group Ownership & Adviser Scope**:
   - Academic decision authority requires the **current assigned adviser** of the document's active, non-disbanded `ResearchClassGroup` holding the `documents.review` permission.
   - Broad repository permissions (`research.view-all`, `documents.download-any`, admin, facilitator, dean) allow document viewing/downloading but do **NOT** grant academic review mutation rights.

2. **VERSIONING & VOID Protection**:
   - Review mutations (comments, resolutions, decisions, corrections) are strictly restricted to `CURRENT` document versions (`is_current === true`). `VOID` versions are historical and read-only.

3. **Acceptance Blocking Rule**:
   - A server-side check inside `ReviewDocument` blocks marking a document as `accepted` if it contains unresolved findings of `revision` or `critical` severity. Informational `comment` severity findings do not block acceptance.

4. **Controlled Decision Correction**:
   - Final review decisions are historical evidence and are never silently overwritten or deleted.
   - Correcting a decision marks the original `DocumentReview` as `is_superseded = true` and creates a new `DocumentReview` record referencing `supersedes_review_id` with a mandatory `correction_reason`.

5. **Phase Boundary & Zero Side-Effects**:
   - Review decisions do NOT automatically create Phase 17 `RevisionRequest` or Phase 18 progress milestone updates, nor do they dispatch notifications (Phase 23). Review activity is logged to `document_review_audits`.

---

## Technical Stack & Database Schema

- **Models**:
  - `Document` (`app/Models/Document.php`)
  - `DocumentReview` (`app/Models/DocumentReview.php`): Stores `supersedes_review_id`, `is_superseded`, `decision`, `review_notes`, `correction_reason`, `reviewed_at`.
  - `DocumentReviewComment` (`app/Models/DocumentReviewComment.php`): Stores `severity` (`comment`, `revision`, `critical`), `page_number`, `comment`, `resolved_by`, `resolved_at`.
  - `DocumentReviewAudit` (`app/Models/DocumentReviewAudit.php`): Records audit events (`comment_added`, `comment_resolved`, `review_decision_recorded`, `review_decision_corrected`).
- **Support & Actions**:
  - `DocumentReviewerAccess.php`
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

- Tested via `tests/Feature/Documents/AdviserDocumentReviewTest.php`:
  - 11 feature tests, 53 assertions, 0 failures.
- Formatted via Laravel Pint (`vendor/bin/pint --test`).
- Compiled via Vite (`npm run build`).
