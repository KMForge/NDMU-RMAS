# Permissions

Users may hold multiple Spatie roles. UI visibility is convenience only; middleware and policies enforce access.

| Capability | Student | Adviser | Panelist | Facilitator | Dean | Administrator |
|---|---:|---:|---:|---:|---:|---:|
| Own/assigned/college/all research scope | Own | Assigned | Assigned | All | College | All |
| Submit proposal | Yes | No | No | No | No | No |
| Review proposal/documents | No | Assigned | No | Yes | Approval | No |
| Manage defenses | No | No | No | Yes | No | No |
| Create evaluations | No | No | Assigned | No | No | No |
| Reports/export | No | No | No | Yes | College | All |
| Manage users/settings/audit | No | No | No | No | No | Yes |

Seeded permissions include `research.view-own`, `research.view-assigned`, `research.view-college`, `research.view-all`, `research.create`, `research.update-own`, `research.approve`, `proposal.submit`, `proposal.review`, `proposal.approve`, `documents.upload`, `documents.review`, `documents.download`, `revisions.create`, `revisions.resolve`, `defenses.view`, `defenses.manage`, `evaluations.create`, `evaluations.view-own`, `evaluations.view-assigned`, `reports.view`, `reports.export`, `users.manage`, `audit-logs.view`, `settings.manage`, and `notifications.broadcast`.

Permissions never imply unrestricted records. Policies/queries must additionally verify project ownership or membership, current adviser/panel assignment, dean college scope, and explicit administrator permissions. Administrative access to content should be least-privilege and audited.
