# Official Research Workflow: Locked Title Presentation and RES-026

## Authoritative sequence

The Research Title Presentation stage follows this order and may not be skipped:

1. The current Research Group Leader uploads a PDF or DOCX Title Proposal through the existing private `documents` storage workflow. The upload begins as `draft`.
2. The Group Leader explicitly submits the current document for screening. Its status becomes `submitted`.
3. Only the facilitator who owns the group’s Research Class may screen it.
   - `revision_required` requires remarks and leaves the rejected document version in history.
   - `approved_for_presentation` unlocks RES-026 but does not finalize the research title.
4. RES-026 is created with the approved current Title Proposal `Document` as its authoritative source.
5. The student enters exactly three titles. Student identities and academic context remain server-derived.
6. A submitted RES-026 unlocks Title Presentation scheduling for the exact owning facilitator.
7. Scheduling creates a `defenses` aggregate with type `title_presentation`, preserving Phase 21 schedule/reschedule history. It does not create a Proposal or Final Defense.
8. After a schedule exists, the facilitator assigns exactly one `chairperson`, `member_1`, and `member_2` through `defense_panel_assignments`.
9. The same-group current Thesis Adviser cannot be Chairperson. The adviser may be an ordinary member if otherwise eligible.
10. After the event is explicitly marked presented, the facilitator records only Approved Research Title No. `1`, `2`, or `3`. The server resolves the title from the exact linked RES-026 version.
11. The exact assigned Chairperson and two Members apply their Phase 20 signatures to that version.
12. The exact class-scoped Program Coordinator performs an explicit signed action.
13. The exact class-scoped Dean performs the explicit final signed action.
14. Only the Dean action writes the selected title to the canonical `research_projects.title` record.
15. The Dean's final signed action also completes the Phase 18 `research-title-presentation` milestone through `SynchronizeWorkflowMilestone` in the same transaction. No separate facilitator milestone action is required for this locked workflow.

## State model

| Record | States used by this flow |
| --- | --- |
| Title Proposal `documents.status` | `draft`, `submitted`, `revision_requested`, `approved_for_presentation` |
| RES-026 `official_form_instances.status` | `draft`, `submitted`, `endorsed`, `approved` |
| `title_presentations.status` | `scheduled`, `panel_assigned`, `presented`, `awaiting_panel_signatures`, `awaiting_program_coordinator`, `awaiting_dean`, `finalized` |

The `title_presentations` record links one Defense aggregate, one RES-026 instance, and the exact immutable submitted RES-026 version. It does not duplicate document storage, official forms, signatures, defense scheduling, or research progress.

## Authorization

Every mutation rechecks account state, permission, contextual assignment, ownership, and workflow state inside a database transaction with authoritative records locked.

- Upload and submit: current Group Leader with `documents.upload`.
- Screen, schedule, assign panel, complete presentation, and record result: exact owning Research Facilitator with the required permission.
- Panel signatures: exact active `defense_panel_assignments` position plus `evaluations.create`.
- Coordinator signature: active `research_class_actor_assignments` entry for `program_coordinator` plus the RES-026 permission.
- Dean signature: active class assignment for `dean` plus the required permission.

Spatie roles provide permissions, but role names alone never establish academic context.

## History and audit

Rejected document versions, document reviews, superseded schedules, ended panel assignments, official-form versions, and version-bound signatures remain immutable or historical. Relevant audit events include:

- `TITLE_PROPOSAL_DOCUMENT_SUBMITTED`
- `TITLE_PROPOSAL_RETURNED_FOR_REVISION`
- `TITLE_PROPOSAL_APPROVED_FOR_PRESENTATION`
- `RES026_CREATED`, `RES026_SUBMITTED`
- `TITLE_PRESENTATION_SCHEDULED`, `TITLE_PANEL_ASSIGNED`, `TITLE_PANEL_CHANGED`
- `TITLE_PRESENTATION_COMPLETED`, `RES026_APPROVED_TITLE_RECORDED`
- `RES026_PANEL_SIGNED`, `RES026_COORDINATOR_ACTION`, `RES026_DEAN_ACTION`
- `CANONICAL_TITLE_FINALIZED`

## Explicit non-rules

- No OCR or AI title extraction.
- No automatic signatures or multi-role action chaining.
- No assumption that a Title Presentation Chairperson is the RES-037 summary signatory.
- No automatic Phase 18 milestone completion.
- No same-group-adviser ban for ordinary Panel Members.
