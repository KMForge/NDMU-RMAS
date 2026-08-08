# Phase 11 — Student Join Class Requests

Phase 11 rebuilds the class join workflow between student researchers and research facilitators.

## Scope

Implemented:

- Students submit a facilitator-provided research class join code.
- A valid join code creates a pending request only.
- Facilitators review requests for classes they own.
- Facilitators may approve or reject pending requests.
- Approval makes the student an active class member.
- Student class details are available only after approval.
- Rejection history is preserved.
- Join-code guessing is rate-limited.

Not implemented in Phase 11:

- Research group creation
- Student group assignment
- Adviser assignment

Those remain Phase 12.

## Main Backend Flow

1. Student opens `student.dashboard?tab=classes`.
2. Student submits a join code to `POST /student/classes/join`.
3. The request validates the code format and checks the `classes.join` permission.
4. The backend normalizes the code and compares its HMAC fingerprint against active research classes.
5. A valid class creates a `pending` `research_class_enrollments` row.
6. The facilitator sees the pending request in `facilitator.dashboard?tab=join-requests`.
7. The owning facilitator approves or rejects through the class-scoped review routes.
8. Approval changes the request status to `active` and sets `joined_at`.

## Business Rules Enforced Server-Side

- A valid code does not immediately enroll the student.
- A student may have only one active research class enrollment.
- A student may have only one pending join request across all research classes.
- Duplicate pending requests are blocked.
- Rejected records stay in the database.
- A rejected student may request the same class again after cooldown:
  - first rejection: 24 hours
  - second rejection: 48 hours
  - third and later rejection: 72 hours
- After five rejected attempts for the same class, the student must contact the facilitator or administrator.
- Approval re-checks inside a transaction:
  - request is still pending
  - student is not already active in another class
  - class is still active
  - class capacity has not been reached
  - facilitator still owns the class

## Routes

Student:

- `POST /student/classes/join`
  - route name: `student.classes.join`
  - middleware: `auth`, `verified`, `active`, `permission:dashboards.student.view`, `permission:classes.join`, `throttle:class-joining`
- `GET /student/classes/{researchClass}`
  - route name: `student.classes.show`
  - middleware: `auth`, `verified`, `active`, `permission:dashboards.student.view`, `permission:classes.view-enrolled`

Facilitator:

- `PATCH /facilitator/classes/{researchClass}/join-requests/{joinRequest}/approve`
  - route name: `facilitator.classes.join-requests.approve`
  - middleware: `auth`, `verified`, `active`, `permission:dashboards.facilitator.view`, `permission:classes.manage-join-requests`, `throttle:class-join-decisions`
- `PATCH /facilitator/classes/{researchClass}/join-requests/{joinRequest}/reject`
  - route name: `facilitator.classes.join-requests.reject`
  - middleware: same as approve

## Storage and Schema

Join requests are stored in `research_class_enrollments`.

Important fields:

- `research_class_id`
- `student_id`
- `status`: `pending`, `active`, or `rejected`
- `requested_at`
- `joined_at`
- `reviewed_by`
- `reviewed_at`

Phase 11 adds a migration that removes the old one-row-per-class/student unique constraint so rejected history can be preserved as separate records.

## Security Notes

- Uses Laravel validation and Eloquent query builder; no raw user-controlled SQL.
- Join codes are normalized before lookup.
- Stored class join codes are not exposed in join request responses.
- Join-code guessing is throttled by authenticated student.
- Facilitator approval/rejection uses policy authorization and transaction re-checks.
- Student class detail access requires an active enrollment.

## Tests

Covered in `tests/Feature/Classes/ResearchClassJoinRequestsPhase11Test.php`:

- Pending request creation
- Facilitator-scoped request listing
- One active class per student
- One pending request across all classes
- Rejection cooldown and history preservation
- Maximum rejected attempts
- Failed join-code rate limiting
- Transactional approval re-checks
- Owning facilitator authorization
- Approved student class detail access
