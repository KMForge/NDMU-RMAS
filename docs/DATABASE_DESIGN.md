# Database Design

## Current Verified Runtime Baseline

NDMU-RMAS is a Laravel modular monolith using Eloquent ORM. The current repository configuration example uses **MySQL**:

- `DB_CONNECTION=mysql`
- host `127.0.0.1`
- port `3306`
- database example `ndmu_rmas`
- storage engine configuration `InnoDB`

The current Phase 14 documentation also records that its reversible migration was applied successfully to the configured local MySQL database, while automated tests may use SQLite. PostgreSQL/Supabase must therefore not be described as the currently verified application database unless the active deployment configuration is changed and independently verified.

The project currently uses Laravel's integer primary-key convention. Do not introduce UUID/ULID primary keys without an explicit migration strategy. UUIDs are used for private stored document filenames and submission/idempotency values independently of database primary keys.

## General Database Rules

- Use foreign keys for authoritative relationships.
- Index ownership, assignment, status, and common query/filter columns where justified by the actual query path.
- Use unique constraints only for true invariants.
- Keep migrations reversible where practical.
- Do not edit already-applied migrations in non-disposable environments; create a new migration.
- Store document metadata and private object/file paths in the database, not document binary content.
- Use Eloquent/query-builder bound parameters rather than constructing SQL from request input.
- Treat soft deletion, archival, and immutable history as separate business decisions rather than applying soft deletes automatically to every table.

## Core Identity and RBAC Tables

The verified access-control design uses:

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `role_has_permissions`
- `model_has_permissions`

`users.user_type` is identity classification (`student`, `faculty`, `admin`) and does not itself grant feature authorization.

## Research Class and Group Domain

Completed Phases 10–12 introduced/activate the Research Class workflow and Research Group/adviser relationships used by later document phases.

Important relationships include:

- Research Class → owning Research Facilitator.
- Student enrollment/request → Research Class.
- Research Class Group → Research Class.
- Research Class Group Member → Research Class Group + Student.
- Research Class Group → designated `leader_student_id`.
- Research Class Group → one current adviser when accepted.
- Adviser request/history records preserve assignment workflow history according to the completed Phase 12 implementation.

A group is limited by the verified school rule of a maximum of four active student members.

## Documents — Phase 13 Foundation

Modern document ownership uses:

`documents.research_class_group_id → research_class_groups.id`

`documents.user_id` records the uploader for accountability and is not the sole owner.

Important document metadata includes the verified fields used by the current workflow:

- uploader (`user_id`)
- Research Class Group (`research_class_group_id`)
- original filename
- generated stored filename/private storage path metadata
- file type / MIME
- file size
- SHA-256 content hash
- submission token
- version number
- `is_current`
- review status
- submission timestamps
- Phase 14 `document_stage`

Private document binary content remains on the configured private filesystem/storage disk rather than inside the relational database.

## Document Upload Auditing

`document_upload_audits` records upload attempts from the Phase 13 submission workflow. It is separate from successful repository-access auditing introduced by Phase 14.

## Phase 14 Document Stage

Phase 14 adds nullable `documents.document_stage` for controlled submission streams.

Current enum values are:

- `title_proposal`
- `proposal_defense`
- `pre_final_defense`
- `final_defense`
- `final_manuscript`

Existing rows were not assigned a guessed stage. Null stage remains possible for older records and is represented by the application as unclassified where needed.

## Stage-Specific Document Versioning

CURRENT/VOID versioning is now logically scoped by:

`research_class_group_id + document_stage`

Version numbering also follows that stream.

A replacement in Proposal Defense therefore affects only the current Proposal Defense version for that group and must not supersede a current document from another stage.

The implementation uses transaction/locking logic rather than relying on a PostgreSQL-specific partial-index solution because the verified runtime is MySQL and the automated tests may use SQLite.

## Historical Group Membership

Phase 14 creates `research_class_group_member_histories` so read-only repository access can be proven for verified former group members after a group is disbanded.

The disband workflow archives authoritative membership before active membership rows are removed. Historical membership grants repository read access only; it does not restore upload or mutation authority.

No lost history should be reconstructed from guesses.

## Document Access Auditing

Phase 14 creates `document_access_audits` for successful protected repository access.

The verified access audit records document/user context and distinguishes at least:

- `viewed`
- `downloaded`

The current documentation records validated IP, bounded user agent, and access time as part of this audit flow. Failed authorization and missing-file responses are not represented as successful access events.

## Repository Query Design

Phase 14 repository queries are record-scoped before search/filter/pagination. The application validates/allowlists:

- document stage;
- existing review status;
- file type (PDF/DOCX);
- version selection (Current/All Versions);
- date sort direction.

Filename search is bounded and parameterized. Pagination returns 10 records per page and preserves validated query-string state.

## Database Security Boundaries

A database row being retrievable by primary key does not mean the current user may access it. Repository authorization combines RBAC permission with authoritative record relationships such as:

- active Research Group membership;
- archived former membership for read-only disbanded history;
- current adviser assignment;
- facilitator ownership of the Research Class;
- explicit broad administrative permission.

These rules are enforced by application policies/access services and scoped queries, not by trusting IDs submitted by the browser.

## Future Schema Boundary

Planned phases may add consultation, revision, progress milestone, forms, signature, defense, evaluation, notification, broader audit, and analytics structures. Those future schemas must not be documented as implemented until their migrations/models/workflows are verified in the active repository.
