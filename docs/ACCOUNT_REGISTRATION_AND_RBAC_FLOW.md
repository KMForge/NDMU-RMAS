# NDMU-RMAS Account Registration and Dynamic RBAC Flow

## Purpose

NDMU-RMAS separates a person's permanent account classification from their changing work responsibilities.

- `users.user_type` identifies the account as `student`, `faculty`, or `admin`.
- Spatie roles describe current responsibilities, such as Research Facilitator, Thesis Adviser, Panel Member, or Dean.
- Permissions belong to roles. Users receive permissions through one or more assigned roles.
- Application workspaces are opened by permissions, never by a hard-coded role selector during login.

This supports promotion, reassignment, temporary duties, and removal of access without editing application code.

## Database Model

No new role column is added to `users`. Spatie Laravel Permission remains the source of truth for access assignments.

```text
users
  id
  user_type (student | faculty | admin)
  status (pending | active | suspended | rejected)
  approved_at
       |
       | polymorphic assignment
       v
model_has_roles ----> roles ----> role_has_permissions ----> permissions
```

Important tables:

| Table | Purpose |
| --- | --- |
| `users` | Account identity, profile, status, verification, and password hash |
| `roles` | Reusable responsibility bundles |
| `permissions` | Stable application capabilities from the permission catalog |
| `model_has_roles` | Many-to-many user-to-role assignments |
| `role_has_permissions` | Many-to-many role-to-permission assignments |
| `model_has_permissions` | Supported by Spatie for exceptional direct grants; normal NDMU-RMAS access should use roles |
| `audit_logs` | Records account, role, permission, and workspace administration events |

No schema migration is required for roleless faculty accounts because the existing many-to-many role relationship already permits zero assignments.

## Student Registration Flow

1. A student opens `/register`.
2. The server validates the student ID, institutional `@ndmu.edu.ph` email, CEAC program, year level, and confirmed strong password.
3. Registration is rate-limited per email and IP address.
4. Inside one database transaction, the application creates:
   - `user_type = student`
   - `status = pending`
   - `approved_at = null`
   - the automatic protected `student` role
5. The application sends the signed email-verification link.
6. The student verifies the institutional email.
7. An administrator approves or rejects the pending registration.
8. Approval changes the account to Active and sets the approval timestamp.
9. The active, verified student may log in and is routed to the Student workspace through `dashboards.student.view`.

A student does not select a role. The server assigns the Student role automatically and transactionally.

## Faculty Account Flow

1. An administrator creates a faculty account in User Management.
2. The account is created as Active, verified, and CEAC-scoped, with no operational role.
3. The faculty member may authenticate, but has no protected feature permissions.
4. The user is sent to `/access-pending`, which only shows account details and logout.
5. An administrator opens **Assign Role** and selects one or more current responsibilities.
6. Spatie synchronizes the role assignments in a database transaction and the change is written to `audit_logs`.
7. The role's dashboard permission makes the matching workspace available immediately.
8. If all roles are later removed, the faculty member returns to Access Pending on the next dashboard request.

Examples:

| Situation | Assignment |
| --- | --- |
| New faculty hire | No role; Access Pending |
| Capstone teacher | Research Facilitator |
| Facilitator who also advises a group | Research Facilitator + Thesis Adviser |
| Faculty assigned to defense evaluation | Panel Member |
| Responsibility removed | Remove only that role |
| No current system responsibility | Clear all roles; Access Pending |

The legacy empty `faculty` role is retained in the catalog only for compatibility, is not assignable, and is detached when the RBAC seeder runs. Faculty identity is represented by `users.user_type`.

## Administrator Flow

- Administrator accounts use `user_type = admin`.
- They must retain a role that provides role-management access.
- The final role-management administrator cannot be stripped of that access or disabled.
- Administrators manage permissions by editing reusable roles rather than hard-coding access on individual users.

## Workspace Resolution

The default workspace is selected from granted dashboard permissions in this order:

1. Administration
2. Research Facilitator
3. College Oversight
4. Thesis Adviser
5. Panel Member
6. Student

Multi-role faculty can use the workspace switcher. Each switch is authorized on the server and audited. A role name alone never bypasses permission middleware or record-scoped policies.

## Authorization and Security Rules

- Login accepts only email, password, and remember-me. It never accepts a client-selected role.
- Passwords use Laravel's hashed model cast.
- Student registration accepts only configured CEAC programs.
- Student IDs and emails are unique database values.
- Registration, login, and verification endpoints are rate-limited.
- Email verification uses a temporary signed URL.
- Suspended, rejected, or unapproved accounts cannot enter a workspace.
- Roleless faculty can open only Access Pending and logout.
- Workspace routes still require authentication, verified email, active approval, explicit permission middleware, and record policies.
- Administrative access changes are recorded with actor, target, old/new values, IP address, user agent, and timestamp.
- Validation uses Laravel rules and data access uses Eloquent/parameterized queries.

## Important Code Locations

| Concern | Location |
| --- | --- |
| Permission and default role catalog | `config/access-control.php` |
| Spatie catalog/database synchronization | `database/seeders/RolePermissionSeeder.php` |
| Student registration validation | `app/Http/Requests/Authentication/RegisterStudentRequest.php` |
| Student registration transaction | `app/Modules/Registration/Actions/RegisterStudent.php` |
| Registration HTTP controller | `app/Http/Controllers/Authentication/RegisteredStudentController.php` |
| Email verification | `app/Http/Controllers/Authentication/VerifyStudentEmailController.php` |
| Faculty account creation | `app/Modules/UserManagement/Actions/ManageUserAccount.php` |
| Role assignment rules | `app/Modules/UserManagement/Actions/ManageRoleAccess.php` |
| Dashboard permission resolution | `app/Modules/Authorization/Services/ResolveUserDashboard.php` |
| Roleless faculty destination | `app/Http/Controllers/AccessPendingController.php` |
| Admin user-management component | `app/Livewire/AdminDashboard.php` |
| Web and authentication routes | `routes/web.php`, `routes/auth.php` |

## Environment and Setup

Do not commit database, SMTP, or Supabase secrets. Configure them only in `.env`.

The verified local runtime currently uses MySQL through WAMP. Its non-secret configuration shape is:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ndmu_rmas
DB_USERNAME=your-local-database-user
DB_PASSWORD=your-secret-password
```

If the approved deployment target is Supabase PostgreSQL, use Laravel's direct PostgreSQL driver instead:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=your-supabase-database-host
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=your-supabase-database-user
DB_PASSWORD=your-secret-password
```

The RBAC implementation uses Eloquent and Spatie's standard tables and does not require engine-specific SQL.

Minimum mail variables for student verification:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-mail-user
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=research@ndmu.edu.ph
MAIL_FROM_NAME="NDMU-RMAS"
```

The verified local runtime currently uses `MAIL_MAILER=log`. In that mode, Laravel writes the verification message and signed URL to `storage/logs/laravel.log` instead of sending an inbox email. Configure SMTP before real-user testing.

Apply the application configuration:

```bash
php artisan config:clear
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
```

The seeder is idempotent: it updates the configured permission metadata and default role permissions, migrates legacy role aliases, disables the legacy Faculty identity role, and does not create a single role column.

## Acceptance Checks

- A new student receives exactly the Student role and remains Pending.
- A student cannot choose or submit a role during registration or login.
- A new faculty account has zero roles.
- A roleless active faculty account logs in to Access Pending.
- A roleless faculty account receives `403` from protected workspaces.
- Assigning Thesis Adviser makes the Adviser workspace available.
- Assigning Research Facilitator + Thesis Adviser makes both workspaces available.
- Clearing faculty roles returns the account to Access Pending.
- Student and administrator identity roles cannot be removed in a way that breaks their required access invariant.
- Every role assignment change appears in Audit Logs.
