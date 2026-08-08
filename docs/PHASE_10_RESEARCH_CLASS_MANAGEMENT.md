# Phase 10 - Research Class Management

## Feature Overview

Phase 10 restores the research class management foundation for the NDMU-RMAS rebuild. It allows an authorized research facilitator to create Capstone research classes, view their owned class cards, open a class detail page, and see the approved student roster for that class.

## Purpose

Research classes are the starting container for Capstone II management. A facilitator creates the class, receives a generated join code, and later phases use that class as the anchor for student join requests, group creation, adviser assignment, document review, and research progress tracking.

## Users Involved

- Research Facilitator: creates and manages owned research classes.
- Student Researcher: appears in the roster only after an active enrollment exists.
- Administrator: manages the roles and permissions that grant access.

## Preconditions

- The user must be authenticated.
- The account must be email verified, active, and approved.
- The user must have `dashboards.facilitator.view` to open the facilitator dashboard.
- The user must have `classes.create` to create a research class.
- The user must have `classes.view-own` and own the class to open class details.

## Implemented Workflow

1. The facilitator opens the facilitator dashboard and selects Capstone Classes.
2. The dashboard loads only classes where `research_classes.facilitator_id` equals the authenticated user ID.
3. The facilitator clicks Create Class.
4. The form submits `creation_token`, `name`, `description`, and `max_students`.
5. Laravel validates and sanitizes the request.
6. The class action generates a secure join code server-side.
7. The class is saved in `research_classes`.
8. The facilitator is redirected back to the class tab.
9. The new class appears as a card.
10. The facilitator opens the class detail page.
11. The detail page shows class information and active student enrollments only.

## Backend Request Flow

`POST /facilitator/classes`

Route middleware:

- `auth`
- `verified`
- `active`
- `permission:dashboards.facilitator.view`
- `permission:classes.create`
- `throttle:class-creation`

Flow:

1. `routes/facilitator.php`
2. `App\Http\Controllers\Facilitator\ResearchClassController::store`
3. `App\Http\Requests\Classes\CreateResearchClassRequest`
4. `App\Modules\Classes\Actions\CreateResearchClass`
5. `App\Models\ResearchClass`
6. `research_classes`
7. JSON success response or redirect with flash message

`GET /facilitator/classes/{researchClass}`

Route middleware:

- `auth`
- `verified`
- `active`
- `permission:dashboards.facilitator.view`
- `permission:classes.view-own`

Flow:

1. `routes/facilitator.php`
2. `App\Http\Controllers\Facilitator\ResearchClassController::show`
3. `ResearchClassPolicy::view`
4. `ResearchClass` with active `ResearchClassEnrollment` records
5. `resources/views/pages/facilitator-class-details.blade.php`

## Validation

Class creation uses `CreateResearchClassRequest`:

- `creation_token`: required UUID
- `name`: required string, 3 to 120 characters
- `description`: optional string, maximum 1,000 characters
- `max_students`: required integer, 1 to 100

The request strips HTML tags and removes control characters from text fields before validation.

## Models and Tables

| Model | Table | Purpose |
| --- | --- | --- |
| `ResearchClass` | `research_classes` | Stores facilitator-owned Capstone classes and encrypted join code metadata. |
| `ResearchClassEnrollment` | `research_class_enrollments` | Stores student membership state for a class. Phase 10 reads active enrollments only. |

## Relationships

- `ResearchClass` belongs to `User` through `facilitator_id`.
- `ResearchClass` has many `ResearchClassEnrollment` records.
- `ResearchClassEnrollment` belongs to `ResearchClass`.
- `ResearchClassEnrollment` belongs to `User` through `student_id`.

## RBAC and Authorization

Implemented controls:

- Dashboard access requires `dashboards.facilitator.view`.
- Class creation requires `classes.create`.
- Class details require `classes.view-own`.
- `ResearchClassPolicy::view` confirms the authenticated facilitator owns the class.
- Another facilitator cannot open a class they do not own.

## Security Controls

- Authentication is enforced by route middleware.
- Verified email and active account middleware are enforced.
- CSRF protection is applied to browser form submissions.
- Server-side validation rejects missing or invalid class data.
- Text fields are sanitized before validation.
- Join code is generated server-side and cannot be chosen by the client.
- Join code is stored encrypted plus a hash fingerprint.
- Eloquent is used for database writes and reads.
- Class listing is scoped to the authenticated facilitator.
- Class detail access is protected by policy to prevent IDOR.
- Duplicate form submissions are reduced through `creation_token` and cache locking.

## Failure Flow

- Invalid input returns validation errors and does not create a class.
- Duplicate `creation_token` returns a conflict response for JSON requests.
- Unauthorized users receive `403 Forbidden`.
- Database write errors are reported server-side and return a safe generic error.

## Files Changed

| File | Purpose |
| --- | --- |
| `routes/facilitator.php` | Reconnected Phase 10 class create and class detail routes. |
| `app/Http/Controllers/Facilitator/DashboardController.php` | Restored facilitator-owned class data loading. |
| `app/Http/Controllers/Facilitator/ResearchClassController.php` | Limited class details to Phase 10 roster data. |
| `app/Modules/Classes/Queries/GetFacilitatorClassData.php` | Scoped class listing to owned classes without Phase 11/12 data. |
| `resources/views/pages/facilitator/classes.blade.php` | Adjusted class cards to Phase 10 fields. |
| `resources/views/pages/facilitator-dashboard.blade.php` | Updated class banner wording for Phase 10. |
| `resources/views/pages/facilitator-class-details.blade.php` | Removed Phase 12 group/adviser controls from the class detail page. |
| `tests/Feature/Classes/ResearchClassManagementPhase10Test.php` | Added Phase 10 feature coverage. |
| `docs/PHASE_10_RESEARCH_CLASS_MANAGEMENT.md` | Added backend flow documentation for capstone tracking. |

## Test Procedures

Run:

```bash
php artisan test tests/Feature/Classes/ResearchClassManagementPhase10Test.php tests/Feature/Authentication/LoginTest.php tests/Feature/Authorization/DynamicRbacTest.php tests/Feature/RebuildFeatureBackendDisabledTest.php
```

Also verify:

```bash
php artisan route:list --except-vendor --path=facilitator
```

## Implemented

- Facilitator-owned class creation
- Secure server-generated join code
- Facilitator class card listing
- Facilitator class detail page
- Active student roster display
- Ownership protection
- Focused Phase 10 tests

## Planned / Future Phase

- Phase 11: student join request submission and facilitator approval/rejection
- Phase 12: research groups, student-to-group assignment, and adviser assignment
- Later phases: document workflows, progress milestones, consultations, evaluations, notifications, and reports

## Known Limitations

- Academic year and academic term are not linked to `research_classes` yet. This needs verification before adding schema.
- Student roster depends on existing active enrollments. Creating those enrollments through join requests belongs to Phase 11.
- Group and adviser assignment controls are intentionally not active in Phase 10.
