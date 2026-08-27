# Audit Logs

Append-only records of important security, administrative, and cross-module workflow actions.

- `Services/AuditLogWriter.php` is the reusable write boundary.
- `Support/AuditEvent.php` contains stable event keys.
- `Support/AuditPayloadSanitizer.php` recursively redacts secrets and bounds state.
- `ValueObjects/AuditRequestContext.php` carries immutable request metadata.
- `Queries/GetAuditLogsForAdmin.php` performs permission-protected viewer queries.

Never record secrets, private object paths, signature specimens, file contents, or signed URLs. Detailed academic evidence remains in the owning feature tables. See `docs/PHASE_24_AUDIT_LOGS.md`.
