# Storage

Production uploads use private Supabase buckets: `research-proposals`, `research-manuscripts`, `research-revisions`, `defense-documents`, `evaluation-sheets`, `archived-research`, and `profile-photos`.

Local fallback paths live under `storage/app/private`; generated non-confidential reports/exports may use `storage/app/public/generated`. Confidential data must never be written to `public/`.

Upload handling must validate MIME type and size, generate a UUID object name, preserve the original name only in metadata, normalize paths, and authorize access with a policy. Downloads use a controlled endpoint or short-lived signed URL (maximum one hour in the provided adapter). Never log signed URLs, tokens, document contents, or the service-role key.

Create all Supabase buckets as private and apply row/object policies in the Supabase dashboard. `SUPABASE_SERVICE_ROLE_KEY` is server-only and must not be exposed to browser JavaScript.
