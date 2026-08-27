# Reports Analytics

Phase 25 provides permission-driven, CEAC-scoped, read-only reports for Administrator, Dean, and Research Facilitator workspaces. `ResearchClassGroup` is the reporting aggregate root. Facilitator queries are additionally constrained to classes whose `facilitator_id` is the authenticated user.

The report catalog, normalized filters, actor scope, query aggregation, CSV protection, PDF rendering, cache keys, and audit integration live in this module. Exports are immediate private responses; no generated report is placed in public or permanent storage. See `docs/PHASE_25_REPORTS_AND_ANALYTICS.md`.
