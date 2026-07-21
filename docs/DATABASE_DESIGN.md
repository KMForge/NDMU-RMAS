# Database design

Supabase PostgreSQL is the production database and Eloquent connects through `DB_CONNECTION=pgsql`. The initial Laravel schema uses integer primary keys; keep that convention consistent.

Domain migrations will be grouped by `core`, `research`, `documents`, `defense`, `evaluation`, `permissions`, `notifications`, `auditing`, and `settings` when those schemas are implemented. Migration directories must be explicitly registered before use because Laravel's default migration path is not recursive.

Use foreign keys and indexes for ownership/assignment lookups, unique constraints for true invariants, timestamps for all mutable records, and soft deletes for recoverable research records. Store file metadata and object paths—not document binary data—in PostgreSQL. Avoid duplicate migrations and never modify one already applied outside local disposable environments.
