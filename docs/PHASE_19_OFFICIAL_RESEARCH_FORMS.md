# Phase 19 — Official Research Forms

Status: **`PHASE 19-OWNED IMPLEMENTATION COMPLETE WITH EXTERNAL DEPENDENCIES`**
Current evidence baseline: `efb72a01c233f1a06c653b942d330281fcbd9362`

The former `92%` weighted figure is retired. It mixed technical foundation work with external institutional decisions and was not reproducible. Phase 19 is complete within its owned scope because the catalog, persistence, immutable versioning, validation, authorization, source binding, printing, and cross-phase integrations are implemented. A form that still needs an NDMU decision or source template is not described as a complete end-to-end workflow.

This document records the official Phase 19 audit, evidence matrix, security invariants, and final status assessment for all 25 research forms in NDMU-RMAS.

## Security Invariants

- **Fail-Closed Authorization:** Access requires `permission AND active academic actor assignment AND exact record scope AND valid state transition`.
- **Administrative Boundary:** Technical admin permission (`users.manage`) does not grant academic signing or validation authority. Academic roles derive strictly from `OfficialFormActorAssignment` or `ResearchClassActorAssignment`.
- **Scope Isolation:** Group-owned instances store only `research_class_group_id`; class-owned instances store only `research_class_id`.
- **Action Semantics:** `ApproveOfficialForm` requires explicit `action`, `targetStatus`, and metadata parameters. A transition is legal only when that exact form/action pair exists in `OfficialFormAuthorization::FORM_WORKFLOWS`; there is no generic approval fallback. Certification uses `CertifyOfficialForm`.
- **Confidential Peer Evaluation:** RES-048 is one private instance per student evaluator. Its roster is frozen from current group membership, ratings are exact whole numbers from 1 through 4, and totals are calculated by the server. No aggregation or release occurs until NDMU supplies that policy.
- **Payload Safety:** Browser payloads cannot set IDs, ownership, source fields, status, actor identities, signatures, institutional decisions, or timestamps. Server-side `OfficialFormPayloadValidator` whitelists allowed input fields per form.
- **Source Binding:** Source type and source ID are an inseparable pair. Source-bound forms fail closed if either is absent, unverified, or mismatched in group ownership.
- **Authoritative Printing:** Print views render only saved, immutable versions of authorized form instances. They never accept arbitrary POST payloads or expose cross-group records.
- **Fail-Safe Fallbacks:** Missing institutional templates (e.g., RES-029) provide a safe, non-crashing UI warning ("Template Under Verification") that exposes no unauthorized data or workflow actions.

## Single Unified 25-Form Status Matrix

| Form | Title / Category | Technical Binding | Source Binding | Verified Workflow | Remaining Gaps / Dependencies | Final Status |
| --- | --- | --- | --- | --- | --- | --- |
| RES-026 | Title Approval Request | Browser payload bound | N/A | Student draft & submit | Institutional adviser workflow (B) | Implemented with External Dependencies |
| RES-027 | Adviser Invitation | Browser payload bound | N/A | Invitation persistence | Adviser invitation response transition (B) | Implemented with External Dependencies |
| RES-028 | Panelist Invitation | Browser payload bound | Phase 21 panel assignments provide context but are not bound to RES-028 | Invitation persistence only | Institutional invitation timing, response, and decline effects (B) | Implemented with External Dependencies |
| RES-029 | Language Editor Invitation | Instance versioning | N/A | Safe fallback view | Institutional template verification (B) | Implemented with External Dependencies |
| RES-030 | Personnel Change Request | Browser payload bound | N/A | Change request persistence; approval disabled | Program Head/Dean decision and assignment mutation rules (B) | Implemented with External Dependencies |
| RES-031 | Adviser Consultation Record | Source-driven ([]) | `ConsultationRecord` | Renders agenda/discussion/notes | Adviser digital signature sign-off (B) | Implemented with External Dependencies |
| RES-032 | Specialist Consultation Sheet | Browser payload bound | N/A | Consultation persistence | Specialist sign-off action (B) | Implemented with External Dependencies |
| RES-033 | Defense Endorsement | Browser payload bound | N/A | Draft persistence; completion transitions disabled | Paper-copy evidence, prerequisites, and Program Head authority (B) | Implemented with External Dependencies |
| RES-034 | Defense Pre-Conference | Browser payload bound | N/A | Checklist persistence | Pre-conference completion semantics (B) | Implemented with External Dependencies |
| RES-035 | Defense Proceedings | Browser payload bound | N/A | Proceedings persistence | Panel proceedings signature action (B) | Implemented with External Dependencies |
| RES-036 | Defense Evaluation | Server-derived immutable evaluation version | `DefenseSchedule` plus `DefenseEvaluation` | Phase 21 roster/schedule and Phase 22 submitted evaluation | None — fully verified | Strictly Implemented |
| RES-037 | Evaluation Summary | Server-derived immutable summary version | `DefenseEvaluationRound` | Phase 22 summary, designated signer, Phase 20 signature, automatic round finalization | None — fully verified | Strictly Implemented |
| RES-038 | Student Endorsement | Browser payload bound | N/A | Draft persistence; completion transitions disabled | Program Head endorsement and adviser-assignment integration (B) | Implemented with External Dependencies |
| RES-039 | Research Revision Chart | Browser payload bound | `DocumentReview` / `RevisionRequest` | Renders source review/request notes | Multi-panelist revision sign-off (B) | Implemented with External Dependencies |
| RES-040 | Instructor Endorsement | Browser payload bound | N/A | Adviser endorses, Instructor receives | None — fully verified | Strictly Implemented |
| RES-041 | Coordinator Endorsement | Browser payload bound | N/A | Instructor endorses, Coordinator receives | None — fully verified | Strictly Implemented |
| RES-042 | Validation Request | Browser payload bound | N/A | Request-scoped validator assignment and isolated RES-043A/B outputs; generic approval disabled | Request completion authority (B) | Implemented with External Dependencies |
| RES-043A | Item Validation Rating | Browser payload bound | RES-042 `OfficialFormInstance` | Assigned validator validates | None — fully verified | Strictly Implemented |
| RES-043B | Rating Summary | Browser payload bound | RES-042 `OfficialFormInstance` | Assigned validator validates | None — fully verified | Strictly Implemented |
| RES-044 | Data Gathering Endorsement | Browser payload bound | N/A | Draft persistence; completion transitions disabled | Prerequisites and exact multi-signature sequence (B) | Implemented with External Dependencies |
| RES-045 | Language Editing Certificate | Browser payload bound | N/A | Assigned Language Editor certifies | None — fully verified | Strictly Implemented |
| RES-046 | Technical Editing Certificate | Browser payload bound | N/A | Assigned Technical Editor certifies | None — fully verified | Strictly Implemented |
| RES-047 | Paper Reproduction Endorsement | Browser payload bound | N/A | Adviser endorses, Dean approves | None — fully verified | Strictly Implemented |
| RES-048 | Self & Peer Evaluation | Private per-evaluator immutable versions | Frozen server-derived group roster | Exact 10 x 1-4 rating matrix and server-derived totals | Aggregation, release, locking, and final acceptance policy (B) | Implemented with External Dependencies |
| RES-049 | Authentic Authorship Certificate | Browser payload bound | Research group membership | Authorship attestation, exact-version signature, and QR verification | None — fully verified | Strictly Implemented |

The former future-phase blockers for RES-036, RES-037, and RES-049 are resolved. RES-028 now has a Phase 21 panel roster, but its invitation/response relationship remains an institutional policy decision and is not treated as an integrated workflow.

## Summary Breakdown

- **Strictly Implemented Forms:** 10 / 25 (`RES-036`, `RES-037`, `RES-040`, `RES-041`, `RES-043A`, `RES-043B`, `RES-045`, `RES-046`, `RES-047`, `RES-049`) — 40% strict workflow coverage
- **Institutional Decision Dependencies:** 14 / 25
- **Institutional Template Dependencies:** 1 / 25 (`RES-029`)
- **Phase 19-Owned Implementation Progress:** **100% Complete**

The 40% figure is a strict form-workflow coverage ratio, not a phase completion percentage. The remaining 60% cannot be completed truthfully until institutional decisions or the missing template are supplied.

## Dependency table

| Dependency | Type | Evidence | Current status | Required owner/action |
| --- | --- | --- | --- | --- |
| RES-036 schedule and panel context | Cross-phase | `ScheduleDefense`, `OpenDefenseEvaluationRound`, `SubmitDefenseEvaluation::createRes036Instance`, `DefenseFormIntegrationTest`, `DefenseEvaluationTest`; commits `0fb621e`, `7660e15` | Resolved | None |
| RES-037 source, signer, and finalization | Cross-phase | `SubmitDefenseEvaluation::generateSummaryAndRes037`, `ApplyOfficialFormSignature`, `FinalizeDefenseEvaluationRound`; commits `eb42a97`, `d098c94` | Resolved | None |
| RES-049 signature and QR verification | Cross-phase | `ApplyOfficialFormSignature::handle`, `OfficialFormSignatureTest`, `OfficialFormVerificationTest`; commit `be5054b` | Resolved | None |
| RES-028 panel invitation | Cross-phase / Institutional policy | Phase 21 stores `DefensePanelAssignment`; no RES-028 source binding or approved response lifecycle exists | Open | NDMU Research Office must define whether invitation precedes assignment or acknowledges it, and what decline changes |
| RES-026 adviser workflow | Institutional policy | Locked workflow has exact panel, coordinator, and dean actions but no approved adviser step | Open | Define whether the adviser signs or approves and at which state |
| RES-027 response | Institutional policy | Registry scaffolds `respond`; route allowlists and approved lifecycle do not expose it | Open | Define issuer, accept/decline states, deadline, and assignment effect |
| RES-030 reassignment | Institutional policy | Request payload persists; no authoritative assignment mutation rule exists | Open | Define approver, replacement rules, effective date, and history |
| RES-031 adviser sign-off | Institutional policy | Consultation source exists; sign transition is only scaffolded | Open | Define signer, timing, and completion effect |
| RES-032 specialist sign-off | Institutional policy | Consultant actor exists; completion semantics are unconfirmed | Open | Define consultant assignment and required signatures |
| RES-033 endorsement evidence | Institutional policy | Adviser/facilitator action scaffolding exists | Open | Define prerequisites, sequence, and final authority |
| RES-034 pre-conference completion | Institutional policy | Schedule source exists; completion semantics are unconfirmed | Open | Define chair authority, attendance evidence, and completion rule |
| RES-035 proceedings signatures | Institutional policy | Proceedings payload exists; required signers are unconfirmed | Open | Define recorder, signatories, and final state |
| RES-038 endorsement chain | Institutional policy | Group/adviser context exists; order and effect are unconfirmed | Open | Define issuer, receiver, acceptance, and adviser-assignment effect |
| RES-039 revision sign-off | Institutional policy | Revision source exists; panel-signature cardinality is unconfirmed | Open | Define exact signers and completion threshold |
| RES-042 completion sign-off | Institutional policy | Validator assignment exists; completion authority is unconfirmed | Open | Define whether facilitator approval, validator output, or both complete the request |
| RES-044 endorsement chain | Institutional policy | Adviser/facilitator actions are scaffolded | Open | Define signers, order, and authorization effect |
| RES-048 final acceptance | Institutional policy | Per-evaluator privacy, frozen roster, 1-4 ratings, version preservation, and server totals are implemented; no release transition exists | Open | Define aggregation audience, release timing, correction/locking, and acceptance |
| RES-029 official form | Institutional template | Catalog target exists; approved template is absent and safe fallback is active | Open | Supply the authoritative template and response instructions |

Generic `respond`, `sign`, `record`, and pre-conference `fill` transitions are deliberately absent from the public route allowlists. Registry scaffolding is not evidence of an approved workflow. This fail-closed limitation must remain until the corresponding institutional decision is confirmed.

## Verification & Build Report

| Test / Build Gate | Command | Result | Details |
| --- | --- | --- | --- |
| Focused Official Forms | `php artisan test tests/Feature/OfficialForms/` | **PASSED** | 80 tests, 398 assertions, 0 failures |
| Official Form filter | `php artisan test --filter=OfficialForm` | **PASSED** | 80 tests, 398 assertions, 0 failures |
| Signature filter | `php artisan test --filter=Signature` | **PASSED** | 30 total; 29 passed, 1 skipped; 128 assertions, 0 failures |
| Defense filter | `php artisan test --filter=Defense` | **PASSED** | 66 tests, 202 assertions, 0 failures |
| Defense evaluation filter | `php artisan test --filter=DefenseEvaluation` | **PASSED** | 26 tests, 84 assertions, 0 failures |
| Consultation filter | `php artisan test --filter=Consultation` | **PASSED** | 21 total; 19 passed, 2 skipped; 77 assertions, 0 failures |
| Revision filter | `php artisan test --filter=Revision` | **PASSED** | 27 tests, 80 assertions, 0 failures |
| Research progress filter | `php artisan test --filter=ResearchProgress` | **PASSED** | 20 tests, 96 assertions, 0 failures |
| Notification filter | `php artisan test --filter=Notification` | **PASSED** | 11 tests, 45 assertions, 0 failures |
| Audit filter | `php artisan test --filter=Audit` | **PASSED** | 21 tests, 95 assertions, 0 failures |
| Dashboard redirect filter | `php artisan test --filter=DashboardRedirectTest` | **PASSED** | 6 tests, 16 assertions, 0 failures |
| Welcome-page filter | `php artisan test --filter=WelcomePageTest` | **PASSED** | 1 test, 12 assertions, 0 failures |
| Full backend suite | `php artisan test --compact` | **PASSED** | 452 total; 428 passed, 24 skipped; 1,920 assertions, 0 failures |
| PHP formatting | `vendor/bin/pint --test` | **PASSED** | No formatting violations |
| Route discovery/cache | `php artisan route:list`; `php artisan route:cache` | **PASSED** | Routes load and cache successfully |
| Migration status | `php artisan migrate:status` | **PASSED** | All repository migrations are applied in the configured PostgreSQL test environment |
| Laravel caches | `php artisan config:cache`; `php artisan view:cache` | **PASSED** | Configuration and Blade templates cache successfully |
| Frontend build | `npm run build` | **PASSED** | Vite production build completed successfully |
| Composer metadata | `composer validate --no-check-publish` | **NOT RUN** | Composer executable is not available on this workstation's PATH |

## Conclusion

Phase 19 remains closed as **`PHASE 19-OWNED IMPLEMENTATION COMPLETE WITH EXTERNAL DEPENDENCIES`**. Cross-phase integrations for RES-036, RES-037, and RES-049 are delivered. The remaining form workflows are explicitly separated into institutional decisions and one missing institutional template; none is hidden as a future Laravel phase or counted as a verified end-to-end workflow.
