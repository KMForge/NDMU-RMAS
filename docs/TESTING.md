# Testing

Run `php artisan test`, `vendor/bin/pint --test`, and `npm run build` before completion. The foundation tests cover health/API access, account approval, role/permission route enforcement, and Supabase path safety.

As domain modules are implemented, add feature tests for protected downloads, research ownership/membership/adviser access, defense schedule collision rules, panel-only evaluation access, policy enforcement, and admin-only settings. Integration tests that contact Supabase must use isolated test credentials and be skipped when credentials are absent; never use production buckets.
