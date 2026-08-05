<?php

return [
    'protected_roles' => [
        'system-administrator',
        'college-dean',
        'research-facilitator',
        'research-adviser',
        'panelist',
        'student-researcher',
    ],

    /*
    | Permission names are stable application capabilities. Administrators may
    | combine them into custom roles, but cannot create arbitrary permissions.
    */
    'permissions' => [
        'Administration' => [
            'users.manage' => ['label' => 'Manage Users', 'description' => 'Create accounts, approve registrations, and manage account status.', 'scope' => 'All managed user accounts'],
            'roles.manage' => ['label' => 'Manage Roles', 'description' => 'Create, update, and delete custom access roles.', 'scope' => 'Custom roles only'],
            'permissions.manage' => ['label' => 'Assign Permissions', 'description' => 'Select catalog permissions for custom roles.', 'scope' => 'Permission catalog'],
            'audit-logs.view' => ['label' => 'View Audit Logs', 'description' => 'Review recorded security and administrative events.', 'scope' => 'System audit records'],
            'settings.manage' => ['label' => 'Manage Settings', 'description' => 'Manage protected system configuration.', 'scope' => 'System settings'],
            'notifications.broadcast' => ['label' => 'Broadcast Notifications', 'description' => 'Send official notifications to system users.', 'scope' => 'Authorized recipients'],
        ],
        'Research' => [
            'research.create' => ['label' => 'Create Research', 'description' => 'Create a research record for an authorized group.', 'scope' => 'Owned research'],
            'research.update-own' => ['label' => 'Update Own Research', 'description' => 'Update research owned by the user or their group.', 'scope' => 'Owned research'],
            'research.view-own' => ['label' => 'View Own Research', 'description' => 'View research owned by the user or their group.', 'scope' => 'Owned research'],
            'research.view-assigned' => ['label' => 'View Assigned Research', 'description' => 'View research explicitly assigned to the user.', 'scope' => 'Assigned research'],
            'research.view-college' => ['label' => 'View College Research', 'description' => 'View research within the configured CEAC college.', 'scope' => 'CEAC'],
            'research.view-all' => ['label' => 'View All Research', 'description' => 'View all research records allowed by administrative policy.', 'scope' => 'System research records'],
            'research.approve' => ['label' => 'Approve Research', 'description' => 'Approve research records at the authorized review level.', 'scope' => 'Assigned review scope'],
        ],
        'Classes' => [
            'classes.create' => ['label' => 'Create Classes', 'description' => 'Create and manage owned Capstone classes.', 'scope' => 'Owned classes'],
            'classes.join' => ['label' => 'Join Classes', 'description' => 'Request enrollment in a research class.', 'scope' => 'Student account'],
            'classes.view-enrolled' => ['label' => 'View Enrolled Classes', 'description' => 'View classes in which the user is enrolled.', 'scope' => 'Enrolled classes'],
            'classes.view-own' => ['label' => 'View Owned Classes', 'description' => 'View classes created by the facilitator.', 'scope' => 'Owned classes'],
            'classes.view-assigned' => ['label' => 'View Assigned Classes', 'description' => 'View classes or groups assigned to an adviser.', 'scope' => 'Assigned classes'],
            'classes.manage-join-requests' => ['label' => 'Manage Join Requests', 'description' => 'Approve or reject student class requests.', 'scope' => 'Owned classes'],
            'classes.manage-groups' => ['label' => 'Manage Research Groups', 'description' => 'Create groups and assign enrolled students.', 'scope' => 'Owned classes'],
            'classes.assign-advisers' => ['label' => 'Assign Advisers', 'description' => 'Assign eligible advisers to research groups.', 'scope' => 'Owned classes'],
        ],
        'Documents and Proposals' => [
            'proposal.submit' => ['label' => 'Submit Proposals', 'description' => 'Submit research proposals for review.', 'scope' => 'Owned research'],
            'proposal.review' => ['label' => 'Review Proposals', 'description' => 'Review proposals within an authorized assignment.', 'scope' => 'Assigned research'],
            'proposal.approve' => ['label' => 'Approve Proposals', 'description' => 'Record an authorized proposal approval.', 'scope' => 'Assigned review scope'],
            'documents.upload' => ['label' => 'Upload Documents', 'description' => 'Upload validated PDF or DOCX research documents.', 'scope' => 'Owned or assigned research'],
            'documents.review' => ['label' => 'Review Documents', 'description' => 'Review documents from assigned researchers.', 'scope' => 'Assigned research'],
            'documents.download' => ['label' => 'Download Documents', 'description' => 'Download documents after record-scoped authorization.', 'scope' => 'Authorized documents'],
            'documents.download-any' => ['label' => 'Download Any Document', 'description' => 'Administrative download access subject to policy checks.', 'scope' => 'System documents'],
        ],
        'Workflow' => [
            'consultations.request' => ['label' => 'Request Consultations', 'description' => 'Book consultation requests with an assigned adviser.', 'scope' => 'Owned research'],
            'consultations.manage-assigned' => ['label' => 'Manage Consultations', 'description' => 'Manage consultation requests from assigned students.', 'scope' => 'Assigned students'],
            'revisions.create' => ['label' => 'Create Revisions', 'description' => 'Issue revision requirements for reviewed research.', 'scope' => 'Assigned research'],
            'revisions.resolve' => ['label' => 'Resolve Revisions', 'description' => 'Submit or resolve authorized revision work.', 'scope' => 'Owned or assigned research'],
            'defenses.view' => ['label' => 'View Defenses', 'description' => 'View defense schedules within the user assignment.', 'scope' => 'Authorized defenses'],
            'defenses.manage' => ['label' => 'Manage Defenses', 'description' => 'Create and manage defense schedules.', 'scope' => 'Authorized defenses'],
            'evaluations.create' => ['label' => 'Create Evaluations', 'description' => 'Submit an evaluation for an assigned defense.', 'scope' => 'Assigned defenses'],
            'evaluations.view-own' => ['label' => 'View Own Evaluations', 'description' => 'View evaluations created by or released to the user.', 'scope' => 'Own evaluations'],
            'evaluations.view-assigned' => ['label' => 'View Assigned Evaluations', 'description' => 'View evaluations for assigned research or defenses.', 'scope' => 'Assigned evaluations'],
        ],
        'Reports' => [
            'reports.view' => ['label' => 'View Reports', 'description' => 'View authorized research and operational reports.', 'scope' => 'Authorized report scope'],
            'reports.export' => ['label' => 'Export Reports', 'description' => 'Export authorized reports without bypassing record scope.', 'scope' => 'Authorized report scope'],
        ],
    ],
];
