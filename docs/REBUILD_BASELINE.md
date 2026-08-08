# NDMU-RMAS Rebuild Baseline

This rebuild keeps the visual dashboard pages and the authentication foundation, then disables feature backends so each module can be rebuilt and documented one at a time.

## Kept Backend

- Login, logout, session authentication
- User account model, account status checks, and profile fields
- Spatie roles and permissions
- Admin user management, role assignment, and system settings
- Basic dashboard routing for each user workspace
- API health status endpoint

## Disabled Feature Backends

- Document upload, review, download, and repository actions
- Consultation booking, approval, rejection, and completion
- Capstone class creation, joining, grouping, adviser assignment, and join decisions
- Research progress and milestone data loading
- Defense scheduling
- Evaluation records
- Revision tracker and revision document uploads
- Official form source loading
- Digital signature enrollment
- Notification data loading outside the auth/admin baseline
- Reports and analytics feature data

Disabled feature routes use `App\Http\Controllers\DisabledFeatureController` and return:

- HTTP `410 Gone` with JSON for API-style requests
- HTTP `410 Gone` for disabled GET routes
- A validation-style error flashed back for normal form submissions

## Rebuild Flow

Rebuild one feature at a time from this baseline. Each restored backend should include its own migration changes, request validation, authorization/policy checks, service/action class, tests, and documentation update.
