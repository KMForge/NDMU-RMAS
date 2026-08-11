# Testing

Run `php artisan test`, `vendor/bin/pint --test`, and `npm run build` before completion. The foundation tests cover health/API access, account approval, role/permission route enforcement, and Supabase path safety.

As domain modules are implemented, add feature tests for protected downloads, research ownership/membership/adviser access, defense schedule collision rules, panel-only evaluation access, policy enforcement, and admin-only settings. Integration tests that contact Supabase must use isolated test credentials and be skipped when credentials are absent; never use production buckets.

## Phase 18 research progress

Run the milestone workflow together with its consultation, revision, and dashboard boundary regressions:

```bash
php artisan test tests/Feature/ResearchProgress/ResearchProgressMilestoneTest.php tests/Feature/Consultations/ConsultationWorkflowTest.php tests/Feature/Revisions/RevisionWorkflowTest.php tests/Feature/StudentDashboardDataTest.php
```

Latest verified result: 60 tests passed, 236 assertions. This suite covers group ownership, automatic idempotent initialization, weighted progress, ordering and override controls, N/A and correction behavior, due dates, evidence scoping, read-only student/adviser access, facilitator ownership, audit events, and the rule that document/consultation/revision activity cannot automatically change a milestone.
