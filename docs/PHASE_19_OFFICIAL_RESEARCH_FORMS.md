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
- Print now resolves the definition's institutional Blade template against the authorized saved instance and current immutable version. Forms whose templates or workflows remain unverified are still classified as partial.
- Print access uses `OfficialFormInstancePolicy::view` and the current saved version only.

## Verification coverage

The focused backend suite covers exclusive ownership, permission-without-assignment denial, exact same-class assignment, cross-class denial, explicit actions, action/status mismatch, RES-026 fail-closed behavior, RES-047 adviser/dean ordering, facilitator denial, system/unknown payload fields, source type/scope/lifecycle rules, RES-043 cross-group denial, print IDOR, specialist assignments, and RES-036/037 blocking.

The completion pass adds immutable V1/V2/V3 draft tests, terminal-state protection, class-actor replacement and deactivation, specialist assignment deactivation, RES-043B validation, and administrator-created specialist shells. Final current counts are recorded after the final verification run.

## Recovery verification report

| Check | Result |
| --- | --- |
| Focused Official Forms suite | Passed: 46 tests, 273 assertions |
| Full regression suite | 302 tests: 270 passed, 9 failed, 23 skipped; 1,298 assertions |
| Failure classification | 5 signature tests hit the intentionally disabled (410) Phase 20 routes; 4 failures are unchanged admin/student/dashboard UI/data expectations outside the Phase 19 implementation files |
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
- Persistence-only templates still need their individual browser fields connected to the authoritative payload workspace before they can be called interactive.

Phase 19 therefore remains **In Progress**.

## Final completion pass architecture

The shared `/official-forms` workspace now lists only policy-visible instances, opens an authoritative `OfficialFormInstance`, binds its `currentVersion` payload to the institutional template, saves edits as a new immutable version, submits through a state-checked action, exposes only verified academic actions, and prints only the saved version. Unexpected top-level browser fields are rejected rather than ignored.

Class-wide Research Instructor, Program Coordinator, and Dean responsibilities are managed through `research_class_actor_assignments`. Authorized class facilitators and administrators can assign, replace, or deactivate eligible faculty. Instance-scoped validators and editors are managed separately through `official_form_actor_assignments`; candidate eligibility requires an active approved Faculty account and the matching Spatie permission. Assignment and deactivation are audited.

### Current 25-form status matrix

`Implemented` below is intentionally strict. `Partial` means the catalog, ownership, payload validator, security foundation, and print routing exist, but a verified workflow, exact connected browser payload, dedicated workflow test, or authoritative dependency is still missing.

| Form | Definition / owner | Payload / source | Actors / workflow | UI / print / tests | Status / blocker |
| --- | --- | --- | --- | --- | --- |
| RES-026 | Group; single per group | Date/topics; group-derived identity | Student draft/submit; approval unverified | Saved workspace and institutional print; security/version tests | Partial â€” approval workflow verification |
| RES-027 | Group; per actor | Invitation payload | Adviser response term not verified | Catalog/template/print foundation | Partial â€” workflow verification |
| RES-028 | Group; per actor | Invitation/defense payload | Panel response term not verified | Catalog/template/print foundation | Partial â€” workflow verification |
| RES-029 | Group; per actor | No verified payload | Language-editor response unverified | No authoritative dedicated template | Blocked â€” template verification |
| RES-030 | Group; repeatable | Personnel-change payload | No automatic reassignment | Persistence foundation only | Partial â€” workflow verification |
| RES-031 | Group; repeatable | Completed `ConsultationRecord` | Source-bound record | Source lifecycle tests; payload UI not fully connected | Partial |
| RES-032 | Group; repeatable | Other-consultant payload | Consultant action unverified | Persistence foundation only | Partial â€” workflow verification |
| RES-033 | Group; per defense context | Defense endorsement payload | Adviser action needs final evidence | Context cardinality foundation | Partial â€” workflow verification |
| RES-034 | Group; repeatable | Pre-conference payload | Record/complete action unverified | Persistence foundation only | Partial â€” workflow verification |
| RES-035 | Group; repeatable | Proceedings payload | Record action not fully integrated | Persistence foundation only | Partial |
| RES-036 | Group; per actor | Evaluation payload | Requires authoritative panel assignment | Runtime creation/action denied | Blocked â€” Phase 21 |
| RES-037 | Group; single per context | Evaluation summary | Requires defense/evaluation source | Runtime creation/action denied | Blocked â€” Phases 21/22 |
| RES-038 | Group; single per group | Endorsement payload | Final actor chain unverified | Persistence foundation only | Partial â€” workflow verification |
| RES-039 | Group; repeatable | `DocumentReview` or `RevisionRequest` | Source lifecycle enforced | Source security tests; UI not fully connected | Partial |
| RES-040 | Group; single per group | Date; group-derived identity | Adviser endorses, assigned Instructor receives | Interactive workspace, exact saved print, ordered/action tests | Implemented |
| RES-041 | Class; single per context | Batch entries | Assigned Instructor endorses, assigned Coordinator receives | Interactive workspace, class actor UI, print, cross-class tests | Implemented |
| RES-042 | Group; repeatable | Validation request payload | Validator assigned to exact request | Persistence/source foundation | Partial |
| RES-043A | Group; per actor | Problem/items; RES-042 source | Exact assigned validator validates | Interactive specialist workspace and institutional print | Implemented |
| RES-043B | Group; per actor | Ratings/date; RES-042 source | Exact assigned validator validates | Interactive specialist workspace, computed mean, workflow test | Implemented |
| RES-044 | Group; repeatable | Endorsement payload | Final endorsement chain unverified | Persistence foundation only | Partial â€” workflow verification |
| RES-045 | Group; per actor | Certificate date | Exact Language Editor certifies | Interactive workspace, actor UI, print, assignment tests | Implemented |
| RES-046 | Group; per actor | Certificate date | Exact Technical Editor certifies | Interactive workspace, actor UI, print, positive workflow test | Implemented |
| RES-047 | Group; single per group | Date/salutation; derived identity | Adviser endorses, assigned class Dean approves | Interactive workspace, exact saved print, ordering tests | Implemented |
| RES-048 | Group; repeatable | Bounded peer ratings | Final acceptance/sign-off unverified | Persistence foundation only | Partial â€” workflow verification |
| RES-049 | Group; single per group | Authorship confirmation | Signature belongs to Phase 20 | Persistence foundation only | Partial â€” Phase 20 signature integration |

Strict fully implemented forms: **7 / 25** (`RES-040`, `RES-041`, `RES-043A`, `RES-043B`, `RES-045`, `RES-046`, `RES-047`).

Weighted Phase 19 progress: **approximately 84%**. The shared security/versioning/assignment/print foundation is mature, while the remaining percentage is primarily exact persistence wiring and verified institutional actions for partial forms. Phase 19 remains **In Progress** because safely blocked or persistence-only forms are not counted as complete.
