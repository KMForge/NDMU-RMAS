# Architecture

NDMU-RMAS is a modular monolith: one Laravel deployment and database, with domain boundaries under `app/Modules`. Shared framework adapters, models, policies, middleware, notifications, and HTTP presentation remain in Laravel's conventional directories.

```text
HTTP or Livewire
  -> Form Request or component validation
  -> Module action/service
  -> Eloquent (repository only for a useful abstraction)
  -> Policy and permission checks
  -> event/job/notification when work is asynchronous
```

Role folders are presentation boundaries only. Shared workflows are not copied per role. Integrations implement contracts in `app/APIs/Contracts`; Supabase Storage is isolated in `app/Clients/Supabase` and `app/Integrations/Supabase`.

The project currently uses Laravel's default integer primary keys. Do not introduce UUID/ULID primary keys without a documented migration strategy. Storage object names use UUIDs independently of database IDs.
