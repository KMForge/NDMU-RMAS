# Environment checklist

- `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`, canonical HTTPS `APP_URL`
- Supabase PostgreSQL values with `DB_SSLMODE=require` and least-privilege credentials
- Server-only Supabase service-role key; no `VITE_` secret variables
- Private buckets and object policies configured
- Production mail, queue, cache, session, logging, backup, monitoring, and alerting configured
- Web root points to `public/`; TLS, security headers, upload limits, worker, and scheduler verified
- Database backup and rollback tested before migrations
