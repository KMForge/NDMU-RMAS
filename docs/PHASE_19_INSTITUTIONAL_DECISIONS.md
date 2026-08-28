# Phase 19 Institutional Decisions and Evidence Register

Date audited: 2026-08-28  
Repository baseline: `efb72a01c233f1a06c653b942d330281fcbd9362`  
Authoritative form source inspected: local `FORMS (1).pdf` (36 pages)

This register separates what is visible in the supplied NDMU forms from what the forms do not define. A printed signature line proves that a signature is required on the paper; it does not by itself define the system actor assignment, action order, rejection state, deadline, notification, or database mutation. Any such unresolved behavior remains fail-closed.

## PDF page map

| PDF page | Form evidence |
| --- | --- |
| 1-2 | Research process flowcharts |
| 3 | Appendix/form list |
| 4 | RES-026 |
| 5 and 7 | RES-030 (duplicate copy) |
| 6 | RES-027 |
| 8 and 13 | RES-031 continuation and first page |
| 9, 11, 16 | RES-034 |
| 10 | RES-028 |
| 12, 14, 17 | RES-035 |
| 15 | RES-036 |
| 18 | RES-032 |
| 19 | RES-033 |
| 20 | RES-037 |
| 21 | RES-038 |
| 22-23 | RES-039 |
| 24 | RES-040 |
| 25 | RES-041 |
| 26 | RES-042 |
| 27 | RES-044 |
| 28 | RES-043A |
| 29 | RES-046 |
| 30 | RES-043B |
| 31 | RES-047 |
| 32 | RES-045 |
| 33 | RES-049 |
| 34 | RES-048 |
| 35-36 | Blank pages |

The supplied 36-page PDF does not contain RES-029. On August 28, 2026, the project owner supplied a supplemental photograph of the authoritative Guidebook page 104, `RES-Form-029 — Invitation to Research Language Editor`. That evidence establishes the printable template, its three editable document fields (`date`, `course`, and `research_title`), the server-derived student roster and assigned language editor, the four stated editor responsibilities, and the Program Coordinator, Language Editor, Dean, and date-conformed blocks. It does not establish an electronic accept/decline lifecycle or assignment mutation rule.

## Evidence and implementation matrix

| Form | PDF evidence | Repository evidence | Classification | Enforced boundary / decision still required |
| --- | --- | --- | --- | --- |
| RES-026 | Three proposed titles; approved title number; chair, two members, coordinator, and dean signatures | Approved current Title Proposal source, exact three titles, scheduled presentation, authoritative panel positions, exact-version signatures, coordinator/dean sequence, canonical title, milestone sync | Strictly implemented | No generic adviser approval is inferred |
| RES-027 | Adviser invitation | Catalog, payload, actor assignment scaffolding | Partial | Accept/decline states, issuer, deadline, and assignment effect are undefined; response route remains unavailable |
| RES-028 | Panelist invitation | Catalog, payload, Phase 21 panel context | Partial | Whether invitation creates or acknowledges an assignment, and decline effects, are undefined |
| RES-029 | Supplemental Guidebook page 104 photograph supplied August 28, 2026 | Dedicated Blade template, whitelisted payload, server-derived group/editor data, immutable versions, workspace and print rendering | Template implemented; institutional decision | Invitation issuer, electronic accept/decline states, deadline, signature sequence, and assignment effect remain undefined; response transition stays unavailable |
| RES-030 | Personnel-change request with institutional approval lines | Payload persistence | Institutional decision | Program Head/Dean identity mapping, decision order, effective date, and assignment-history mutation are undefined; approval is disabled |
| RES-031 | Adviser consultation record and signature | Exact completed `ConsultationRecord` binding, rendering, and printing | Source foundation complete | Signature timing and completion effect remain undefined |
| RES-032 | Other-consultant consultation sheet and signature | Payload and scoped consultant assignment | Partial | Consultant selection and completion/signature threshold remain undefined |
| RES-033 | Defense endorsement and paper-copy requirement | Payload only | Institutional decision | Required paper evidence, prerequisite chain, Program Head mapping, and final authority are undefined; completion actions are disabled |
| RES-034 | Pre-conference checklist | Payload and defense context scaffolding | Institutional decision | Chair authority, attendance evidence, and completion semantics are undefined |
| RES-035 | Defense proceedings | Payload and defense context scaffolding | Institutional decision | Recorder, final signers, and authoritative completion state are undefined |
| RES-036 | Individual defense evaluation | Exact current `DefenseSchedule`, frozen panel context, immutable evaluation, server scoring | Strictly implemented | None within current approved rules |
| RES-037 | Evaluation summary and signatures | Exact `DefenseEvaluationRound`, designated signer, immutable summary, signature finalization | Strictly implemented | None within current approved rules |
| RES-038 | Program Head endorsement to adviser | Group/adviser records exist | Institutional decision | Program Head mapping and whether this creates, confirms, or follows adviser assignment are undefined; transitions are disabled |
| RES-039 | Revision chart | Exact completed review/revision-request binding, rendering, and printing | Source foundation complete | Exact signers and completion threshold remain undefined |
| RES-040 | Adviser endorsement to research instructor | Explicit adviser endorsement and instructor receipt | Strictly implemented | None within current approved rules |
| RES-041 | Research instructor endorsement to coordinator | Explicit instructor endorsement and coordinator receipt | Strictly implemented | None within current approved rules |
| RES-042 | Validation request and adviser notation | Request-scoped validator assignment and isolated RES-043A/B linkage | Partial | What formally completes the request is undefined; generic facilitator approval is disabled |
| RES-043A | Item-level instrument validation | Assigned validator, exact source request, scoped validation action | Strictly implemented | None within current approved rules |
| RES-043B | Validation rating summary | Assigned validator, exact source request, scoped validation action | Strictly implemented | None within current approved rules |
| RES-044 | Data-gathering endorsement with panel, adviser, coordinator, and dean lines | Payload only | Institutional decision | Prerequisites, exact signers, order, and completion threshold are undefined; transitions are disabled |
| RES-045 | Language-editing certificate | Assigned editor certification | Strictly implemented | None within current approved rules |
| RES-046 | Technical-editing certificate | Assigned editor certification | Strictly implemented | None within current approved rules |
| RES-047 | Reproduction endorsement | Adviser endorsement and dean approval | Strictly implemented | None within current approved rules |
| RES-048 | Self/peer evaluation; ten criteria; 4/3/2/1 scale | One private instance per evaluator, frozen current roster, exact 10-row matrix, 1-4 validation, immutable snapshots, server totals | Technical foundation complete | Aggregation audience, release time, corrections/locking, signature meaning, and final acceptance are undefined |
| RES-049 | Authentic authorship certificate | Group membership, exact-version student attestation, signature and QR verification | Strictly implemented | None within current approved rules |

## Fail-closed decisions applied in this audit

- Removed the generic transition fallback. An action now exists only when the exact form/action transition is explicitly configured.
- Disabled unsupported approval/completion transitions for RES-030, RES-033, RES-038, RES-042, and RES-044.
- Kept generic `respond`, `sign`, `record`, and pre-conference completion actions outside public route allowlists where institutional semantics are unverified.
- Replaced the RES-029 fallback with the evidence-backed page 104 template while keeping its unverified response transition unavailable.
- Kept RES-048 private to its evaluator; administrators, peers, advisers, facilitators, and other faculty do not receive implicit access.
- Added no database migration because existing instance, version, actor-assignment, and source-snapshot structures support the verified requirements.

## Institutional questions required before expansion

1. Who is the canonical Program Head in the system, and is that actor distinct from Program Coordinator and Research Facilitator?
2. For RES-030, who approves, in what order, and which assignment records must change atomically?
3. For RES-033 and RES-044, what evidence is mandatory and what exact signature threshold finalizes the form?
4. For RES-038, does the form create an adviser assignment, acknowledge an existing one, or merely document it?
5. For RES-042, does assignment of a validator change request status, or do completed RES-043A/B records complete it?
6. For RES-048, who may see individual ratings, when are aggregates released, can a student correct a submission, and which action is final acceptance?
7. For RES-029, who issues the invitation, what electronic accept/decline states are required, what is the deadline, in what order do the Coordinator, Language Editor, and Dean sign, and does acceptance create or only confirm an editor assignment?

Until NDMU answers these questions, the listed workflows must remain non-actionable rather than infer institutional policy from printed labels.
