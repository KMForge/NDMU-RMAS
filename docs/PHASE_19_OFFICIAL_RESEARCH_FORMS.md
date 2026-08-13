# Phase 19 — Official Research Forms (Correctness Recovery)

Status: **In Progress**
Recovery baseline: `dffbd16df88762e564549781901fe66332281265`

This document records the evidence audit and fail-closed corrections made after the initial Phase 19 foundation. A form being visible in Blade does not prove an approval workflow. Permissions, actor identity, ownership, source binding, and state transition must all be independently established.

## Security invariants

- Authorization is `permission AND exact active actor assignment AND exact record scope AND valid state transition`.
- `initiated_by` grants draft ownership/visibility only; it never grants an academic action.
- Group-owned instances store only `research_class_group_id`; class-owned instances store only `research_class_id`.
- `ApproveOfficialForm` requires the caller to pass `action`, `targetStatus`, and metadata explicitly. Status never selects an action.
- Only `approve`, `endorse`, and `receive` are accepted by that action. Certification remains in `CertifyOfficialForm`.
- Browser payloads cannot set IDs, ownership, source fields, status, actor identities, signatures, institutional decisions, or timestamps.
- Source type and source ID are an inseparable pair. Source-bound forms fail closed if either is absent or the lifecycle/scope check fails.
- Print uses the authorized saved instance and its current immutable version. It does not accept an arbitrary payload or expose another group's form.

## Evidence and action matrix

| Form | Template evidence | Verified backend action | Actor source | Workflow status |
| --- | --- | --- | --- | --- |
| RES-026 | Panel chairman/member lines, Program Coordinator, College Dean | Draft + submit only | Group leader for draft | Approval blocked; adviser is not a signer in the template |
| RES-027–030 | Invitations/change request fields exist | Persistence only | Instance assignment where configured | Approval/response transition not verified |
| RES-031 | Consultation rows and adviser signature | Source-bound persistence | Completed, non-superseded `ConsultationRecord` | Signature transition not verified |
| RES-032–035 | Consultation/defense fields exist | Persistence only | Group/instance context | Generic approval removed |
| RES-036–037 | Defense evaluation UI exists | None | Future defense panel assignment | Blocked until Phases 21/22 |
| RES-038–039 | Endorsement/revision UI exists | Source-bound persistence for RES-039 | Group; authoritative review/revision source | Generic approval removed |
| RES-040 | Adviser endorsement, instructor receipt | `endorse`, then `receive` | Group adviser; instance `research_instructor` | Active and ordered |
| RES-041 | Instructor endorsement, coordinator receipt | `endorse`, then `receive` | Exact active class assignment | Active and ordered |
| RES-042 | Validation request | Persistence only | Group | Generic approval removed |
| RES-043A/B | Validator item/rating forms | `validate` | Pre-existing validator assignment on same-group RES-042 source | Active |
| RES-044 | Data-gathering endorsement | Persistence only | Group adviser/panel context | Transition not yet verified |
| RES-045–046 | Editor certificate templates | `certify` | Exact language/technical editor assignment | Active |
| RES-047 | Adviser endorsement and College Dean approval | `endorse`, then `approve` | Group adviser; exact class `dean` assignment | Active and ordered |
| RES-048–049 | Peer evaluation/authorship declaration | Persistence only | Group | Generic approval removed |

## Ownership and assignment authority

`official_form_actor_assignments` remains instance-scoped. It is appropriate for a validator, editor, consultant, panelist, or recipient assigned to one form instance.

Class-wide institutional responsibility is stored separately in `research_class_actor_assignments`. This avoids treating an actor on one unrelated group form as the instructor/coordinator/dean of every form in that class.

| Assignment | Record | Who may assign | Eligibility |
| --- | --- | --- | --- |
| Research Instructor | `research_class_actor_assignments` | Class facilitator or administrator | Faculty with RES-041 fill/endorse permission |
| Program Coordinator | `research_class_actor_assignments` | Class facilitator or administrator | Faculty with RES-041 receive permission |
| College Dean | `research_class_actor_assignments` | Class facilitator or administrator | Faculty with RES-047 approve permission |
| Adviser | `research_class_groups.adviser_id` | Existing class/group workflow | Exact group adviser |
| Validator/editor/specialist | `official_form_actor_assignments` | Authorized instance assigner | Faculty plus matching form permission |

Assignment creation is audited. The institutional business process deciding who may designate class-level officers still needs product/UI integration; the backend mechanism is explicit and record-scoped.

## Source-binding matrix

| Form | Allowed source | Required checks |
| --- | --- | --- |
| RES-031 | `ConsultationRecord` | Same group, `consulted_at` present, not superseded |
| RES-039 | `DocumentReview` | Document belongs to same group, reviewed, not superseded |
| RES-039 | `RevisionRequest` | Same group, not invalidated, not cancelled |
| RES-043A/B | `OfficialFormInstance` (RES-042 only) | Same group and target validator already has an active assignment on source RES-042 |

All other source model types are rejected. RES-043A/B cannot create their own validator authority.

## Payload-to-template alignment

The whitelist below contains browser-editable content only. Actor names, student rosters, research/class identity, signatures, reviewer remarks, approvals, and calculated values must come from authoritative records or future workflow actions.

| Form | Accepted browser fields | Server-derived/read-only examples |
| --- | --- | --- |
| RES-026 | `date`, `topics` | students, approved title/number, remarks, panel/coordinator/dean |
| RES-027 | `date`, `course`, `research_title` | adviser, students, issuer/signature |
| RES-028 | `date`, `panel_role`, `defense`, `course`, `defense_date`, `time`, `venue`, `research_title` | panelist, students, coordinator/dean |
| RES-029 | none until a template is verified | all actor/response fields |
| RES-030 | `date`, `degree_program`, `research_title`, `personnel_type`, `current_names`, `proposed_replacement`, `reasons` | students and approving/noting officers |
| RES-031 | none; authoritative consultation source | researchers, adviser, consultation rows/signature |
| RES-032 | `date`, `consultant_types`, `specific_concerns`, `recommendations`, `follow_up_date` | students and consultant signature |
| RES-033 | `date`, `defense_type`, `defense_date`, `time` | students, title, adviser, receiver |
| RES-034 | `date`, `time`, `defense_type`, `issues`, `pages` | students, adviser, title |
| RES-035 | `date`, `defense_type`, `comments` | students, title, adviser/panel signatures |
| RES-038 | `date`, `day`, `month_year` | adviser, students, title, officer signatures |
| RES-039 | `revisions`, `recommendation`, `date` | research identity and reviewers |
| RES-040 | `date` | title, students, adviser/instructor identity |
| RES-041 | `date`, `subject_number`, `descriptive_title`, `entries` | instructor/coordinator identity |
| RES-042 | `date`, `descriptive_title`, `course` | students, program, title, adviser/validator |
| RES-043A | `problem`, `items`, `date` | researchers, title, validator identity |
| RES-043B | `ratings`, `date` | researchers, title, mean, validator identity |
| RES-044 | `date`, `salutation` | group/title and adviser/coordinator/dean |
| RES-045–047 | `date` (plus `salutation` on RES-047) | group/title and editor/adviser/dean identity |
| RES-048 | `evaluation_phase`, `ratings`, `date` | member names, totals, evaluator signature |
| RES-049 | `authorship_confirmed` | researchers and digital-signature metadata |

Array values are bounded and recursively limited; strings are trimmed and length-limited. Blade output remains escaped.

## UI and print audit

- All located RES-026–049 Blade templates were inspected; RES-029 has no verified dedicated template.
- RES-026 and RES-047 were checked against their actual signature labels, not inferred role names.
- The current generic print page is persistence/version display, not proof that a form's institutional layout or signatures are complete.
- Print access uses `OfficialFormInstancePolicy::view` and the current saved version only.

## Verification coverage

The focused backend suite covers exclusive ownership, permission-without-assignment denial, exact same-class assignment, cross-class denial, explicit actions, action/status mismatch, RES-026 fail-closed behavior, RES-047 adviser/dean ordering, facilitator denial, system/unknown payload fields, source type/scope/lifecycle rules, RES-043 cross-group denial, print IDOR, specialist assignments, and RES-036/037 blocking.

Latest focused result during recovery: **40 Official Forms tests, 251 assertions, 0 failures**. Final repository-wide verification is recorded in the implementation handoff.

## Recovery verification report

| Check | Result |
| --- | --- |
| Focused Official Forms suite | Passed: 40 tests, 251 assertions |
| Full regression suite | 296 tests: 264 passed, 9 failed, 23 skipped; 1,275 assertions |
| Failure classification | 5 signature tests hit the intentionally disabled (410) Phase 20 routes; 4 failures are in unchanged dashboard UI/data expectations outside the Phase 19 implementation files |
| Pint | Passed |
| Vite production build | Passed: 58 modules transformed |
| Blade compilation | Passed |
| PostgreSQL migrations | Both recovery migrations applied in batch 12 |

The full suite is therefore not represented as green. The Phase 19 focused suite is green, while unrelated disabled-feature and dashboard assertions remain repository-level blockers.

## Completion accounting

Under the prompt's strict definition, a form is fully implemented only when definition, ownership, payload validation, actor authority, workflow, authorization, UI binding, version persistence, exact print binding, tests, and documentation all exist. None of the 25 forms currently satisfies every item because interactive UI saving and exact institutional print binding are still incomplete.

- Fully implemented forms: **0 of 25 (strict form-completion percentage: 0%)**
- Partial/foundation forms: **23**
- Phase 21/22 blocked forms: **2 (RES-036 and RES-037)**

This strict percentage does not mean the persistence/security foundation is absent; it prevents partial forms from being reported as complete without an approved weighting model.

## Remaining blockers

- Institutional approval mappings for forms not listed as active above require signed process evidence.
- RES-029 needs a verified template.
- RES-036/037 require authoritative defense/panel/evaluation records from Phases 21/22.
- Per-form interactive saving and exact institutional print layouts remain incomplete.
- Class actor assignment needs its final administrator/facilitator UI flow.

Phase 19 therefore remains **In Progress**.
