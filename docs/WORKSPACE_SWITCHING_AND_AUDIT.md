# Workspace Switching and Audit Trail

## Purpose

NDMU-RMAS supports users who hold more than one responsibility at the same time. For example, one faculty account may be both a **Research Facilitator** (or Program Coordinator) and a **Thesis Adviser**. Workspace switching lets that user move between separate dashboards without creating a second account or combining unrelated sidebar menus.

---

## 1. Access Limitations & Employee Security Model

To prevent unauthorized employees from opening restricted portals or executing actions outside their assigned duties, access limitations are enforced **strictly server-side** across 3 security layers:

```
[ HTTP Request ]
       │
       ▼
[ Layer 1: Route Middleware ] ──► Validates auth, email verification, active account, and Spatie role
       │
       ▼
[ Layer 2: Workspace Authorization Guard ] ──► Validates Spatie permission for target workspace
       │
       ▼
[ Layer 3: Record-Scoped Policies ] ──► Validates department, class, and group ownership/membership
```

### Server-Side Limitation Layers

1. **Route Middleware Guards (`routes/*.php`)**:
   - Every dashboard and action endpoint is wrapped in server-side middleware: `middleware(['auth', 'verified', 'active_account', 'role:...'])`.
   - **Protection**: If an unauthorized employee attempts to type a URL (e.g., `/admin/dashboard` or `/facilitator/dashboard`), Laravel returns an immediate **HTTP 403 Forbidden** response. Client UI tampering cannot bypass server middleware.

2. **Workspace Switch Authorization (`SwitchWorkspace.php`)**:
   - The `/workspace/{workspace}` switch endpoint checks `ResolveUserDashboard::routeForWorkspace($user, $workspace)`.
   - If the user does not possess the explicit Spatie permission for that workspace, the server executes `abort(403)`.

3. **Record-Scoped Policies (`app/Policies/`)**:
   - Even within an authorized dashboard, policies enforce record-level ownership, adviser assignment, and college scope.

---

## 2. Tracking Role Capacity & Action Accountability

### How to Distinguish Actions Executed as "Facilitator" vs. "Adviser" vs. "Admin"

When an employee with multiple roles modifies or approves a record, the system records the **Active Role Capacity** under which the action took place:

```
+-----------------------------------------------------------------------------------------------+
| AUDIT TRAIL LOG ENTRY                                                                          |
+---------------------+-------------------------------------------------------------------------+
| Actor               | Dr. Rosario Dela Paz (r.dela-paz@ndmu.edu.ph)                           |
| Active Role Context | RESEARCH FACILITATOR                                                    |
| Event Type          | DOCUMENT_REVIEW.APPROVED                                                |
| Target Entity       | DocumentRecord #14 (Proposal Chapter 1)                                |
| Action Details      | Approved document proposal under Research Facilitator workspace.        |
| State Change        | status: under_review ──► approved                                       |
| Timestamp           | Aug 10, 2026 04:03 PM                                                   |
+---------------------+-------------------------------------------------------------------------+
```

### Sequential Event Correlation

Administrators trace exact action sequences in **Admin → Audit Logs**:
1. **Step 1 (Workspace Switch)**: `workspace.switched` — `old: adviser → new: facilitator`
2. **Step 2 (Action Execution)**: `document.approved` — Executed under active `facilitator` context.

---

## 3. Workspace Access Matrix

Workspace access is permission-based through Spatie Laravel Permission.

| Workspace | Required Permission | Destination Route |
| --- | --- | --- |
| Administration | `dashboards.admin.view` | `admin.dashboard` |
| Research Facilitator | `dashboards.facilitator.view` | `facilitator.dashboard` |
| College Oversight | `dashboards.dean.view` | `dean.dashboard` |
| Thesis Adviser | `dashboards.adviser.view` | `adviser.dashboard` |
| Panel Member | `dashboards.panelist.view` | `panelist.dashboard` |
| Student Researcher | `dashboards.student.view` | `student.dashboard` |

---

## 4. User Flow

1. An administrator assigns one or more roles to a user in **Admin → User Management**.
2. Spatie resolves permissions supplied by all assigned roles.
3. The workspace switcher lists only dashboards for which the user has matching dashboard permissions.
4. The user selects a workspace from the dropdown header component.
5. The browser sends `POST /workspace/{workspace}` with a valid CSRF token.
6. The server checks authentication, verified email, active account status, rate limits, and dashboard permissions.
7. A structured `workspace.switched` audit event is written with the verified destination in `actor_context`; the active workspace is then saved in `session('active_workspace')`.
8. The user is redirected to that workspace dashboard.

---

## 5. Security Controls

- Server-side permission checks govern available workspaces.
- Hiding UI controls cannot bypass endpoint authorization.
- Requests are rate-limited to 30 attempts per minute per session.
- Client inputs cannot forge the audit event actor or active workspace context.

---

## 6. Implementation Files

| Responsibility | File |
| --- | --- |
| Workspace Catalog & Permission Resolution | `app/Modules/Authorization/Services/ResolveUserDashboard.php` |
| Authorized Switch Action & Audit Write | `app/Modules/Authorization/Actions/SwitchWorkspace.php` |
| HTTP Endpoint Controller | `app/Http/Controllers/WorkspaceController.php` |
| Active Workspace Middleware | `app/Http/Middleware/TrackActiveWorkspace.php` |
| Reusable Header Switcher Component | `app/View/Components/WorkspaceSwitcher.php` |
| Switcher Blade UI | `resources/views/components/workspace-switcher.blade.php` |
| Audit Model | `app/Models/AuditLog.php` |
| Audit Database Migration | `database/migrations/2026_08_10_000001_create_audit_logs_table.php` |
| Reusable Audit Writer | `app/Modules/AuditLogs/Services/AuditLogWriter.php` |
| Phase 24 Audit Hardening Migration | `database/migrations/2026_08_25_000001_harden_audit_logs_for_phase24.php` |
| Admin Audit Viewer UI | `resources/views/admin/audit-logs.blade.php` |
| Route Definitions | `routes/web.php` |
| Feature Tests | `tests/Feature/Authorization/WorkspaceSwitchingTest.php` |
