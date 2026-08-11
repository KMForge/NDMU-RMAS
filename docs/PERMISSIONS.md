# Dynamic RBAC

NDMU-RMAS separates identity classification from authorization:

- `users.user_type` is only `student`, `faculty`, or `admin`. It describes the account and never grants access by itself.
- Roles are database records and reusable collections of permissions.
- A user can hold multiple roles through Spatie's `model_has_roles` table.
- Roles receive multiple permissions through `role_has_permissions`.
- Routes, controllers, requests, policies, and actions authorize permission names and record scope—not role names.

For example, a faculty member may simultaneously hold `faculty`, `program-coordinator`, `research-facilitator`, and `thesis-adviser`. Promotion or reassignment changes their role rows; it does not require another account or a source-code change.

## Default roles

The initial editable roles are Administrator, Faculty, Student, Research Facilitator, Program Coordinator, Thesis Adviser, Panel Member, Department Chair, and Dean. Administrators can create additional roles and combine permissions from the catalog. Only deletion of the Administrator role is protected, and its critical access-management permissions cannot be removed.

Legacy role names are seeded as non-assignable compatibility aliases during the transition. Existing users are moved to the canonical roles and the old assignment is removed. The aliases can be retired after every deployment and external integration uses canonical names.

## Permission catalog

`config/access-control.php` defines stable application capabilities and their initial metadata. `RolePermissionSeeder` persists each capability's display name, description, module, and scope in the `permissions` table. It installs default role mappings only when a role is first created, so running the seeder later does not overwrite administrator changes.

Dashboard permissions are explicit (`dashboards.student.view`, `dashboards.adviser.view`, and so on). Domain capabilities include granular research, class, document, proposal, consultation, revision, defense, evaluation, report, user, role, settings, notification, and audit permissions.

An arbitrary permission name has no effect until backend code checks it. This is intentional: the catalog is flexible for role composition while security enforcement remains reviewable in source code.

## Authorization flow

```text
authenticated + verified + active account
  -> route permission middleware
  -> request/component validation
  -> record-scoped policy (owner/member/assignment/college)
  -> domain action and transaction
  -> audit important changes
```

UI visibility is convenience only. Hiding a menu or button is not authorization. Every protected HTTP action must repeat the permission/policy check server-side.

## Administration safeguards

- Administrators may assign several roles and update a user's primary type from User Management.
- A user cannot modify their own role assignments.
- The final active access-control administrator cannot be suspended or lose role-management access.
- Assigned roles must exist under the `web` guard.
- Role and user-access changes are written to `audit_logs` without passwords or other secrets.

## Schema

The project uses Spatie's normalized schema instead of duplicate RBAC tables:

| Purpose | Table |
|---|---|
| Users and primary type | `users` |
| Role definitions and descriptions | `roles` |
| Permission catalog and metadata | `permissions` |
| User-to-role many-to-many | `model_has_roles` |
| Role-to-permission many-to-many | `role_has_permissions` |
| Exceptional direct user permissions (normally avoided) | `model_has_permissions` |

Record access remains narrower than a permission alone. Policies and queries must still verify ownership, group membership, adviser/panel assignment, facilitator ownership, CEAC scope, and explicit administrative authority.

## Phase 18 progress permissions

| Permission | Intended scope |
| --- | --- |
| `progress.view-own` | Current/former student reads proven group history |
| `progress.view-assigned` | Current or verified historical adviser reads assigned group history |
| `progress.view-owned-classes` | Facilitator reads groups in currently owned classes |
| `progress.manage-owned-classes` | Facilitator mutates active milestones only in currently owned classes |
| `progress.override-order` | Adds controlled sequence/direct-completion override authority; ownership still required |
| `progress.view-all` | Explicit administrator system-wide read visibility only |

None of these permissions bypasses `ResearchProgressAccess` or `ResearchGroupMilestonePolicy`. Admin visibility does not grant milestone mutation.
