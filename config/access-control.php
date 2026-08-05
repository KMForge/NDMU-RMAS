<?php

return [
    // Only deletion is protected. Permissions on every role remain editable;
    // critical administrator capabilities are guarded by the domain action.
    'protected_roles' => ['administrator'],

    'legacy_role_aliases' => [
        'system-administrator' => 'administrator',
        'student-researcher' => 'student',
        'research-adviser' => 'thesis-adviser',
        'panelist' => 'panel-member',
        'college-dean' => 'dean',
    ],

    'roles' => [
        'administrator' => [
            'label' => 'Administrator',
            'description' => 'Manages system users, access control, configuration, auditing, and reporting.',
            'user_type' => 'admin',
            'permissions' => ['dashboards.admin.view', 'research.view-all', 'documents.download', 'documents.download-any', 'defenses.view', 'reports.view', 'reports.export', 'users.manage', 'roles.manage', 'permissions.manage', 'audit-logs.view', 'settings.manage', 'notifications.broadcast'],
        ],
        'faculty' => [
            'label' => 'Faculty',
            'description' => 'Base faculty identity role. Operational access is added through responsibility roles.',
            'user_type' => 'faculty',
            'permissions' => [],
        ],
        'student' => [
            'label' => 'Student',
            'description' => 'Student researcher access to owned records and enrolled classes.',
            'user_type' => 'student',
            'permissions' => ['dashboards.student.view', 'research.view-own', 'research.create', 'research.update-own', 'proposal.submit', 'documents.upload', 'documents.download', 'consultations.request', 'classes.join', 'classes.view-enrolled', 'revisions.resolve', 'defenses.view', 'evaluations.view-own'],
        ],
        'research-facilitator' => [
            'label' => 'Research Facilitator',
            'description' => 'Coordinates research classes, groups, reviews, defenses, and reporting.',
            'user_type' => 'faculty',
            'permissions' => ['dashboards.facilitator.view', 'research.view-all', 'proposal.review', 'proposal.approve', 'documents.review', 'documents.download', 'revisions.create', 'defenses.view', 'defenses.manage', 'evaluations.view-assigned', 'reports.view', 'reports.export', 'notifications.broadcast', 'classes.create', 'classes.view-own', 'classes.manage-join-requests', 'classes.manage-groups', 'classes.assign-advisers'],
        ],
        'program-coordinator' => [
            'label' => 'Program Coordinator',
            'description' => 'Coordinates program-level research activity and approvals.',
            'user_type' => 'faculty',
            'permissions' => ['dashboards.facilitator.view', 'research.view-college', 'research.approve', 'proposal.approve', 'documents.review', 'documents.download', 'defenses.view', 'reports.view', 'reports.export'],
        ],
        'thesis-adviser' => [
            'label' => 'Thesis Adviser',
            'description' => 'Advises assigned research groups and reviews their work.',
            'user_type' => 'faculty',
            'permissions' => ['dashboards.adviser.view', 'classes.serve-as-adviser', 'research.view-assigned', 'proposal.review', 'documents.upload', 'documents.review', 'documents.download', 'consultations.manage-assigned', 'revisions.create', 'revisions.resolve', 'defenses.view', 'evaluations.view-assigned', 'classes.view-assigned'],
        ],
        'panel-member' => [
            'label' => 'Panel Member',
            'description' => 'Reviews and evaluates assigned research defenses.',
            'user_type' => 'faculty',
            'permissions' => ['dashboards.panelist.view', 'research.view-assigned', 'documents.download', 'defenses.view', 'evaluations.create', 'evaluations.view-own', 'evaluations.view-assigned'],
        ],
        'department-chair' => [
            'label' => 'Department Chair',
            'description' => 'Provides department-level research oversight and approvals.',
            'user_type' => 'faculty',
            'permissions' => ['dashboards.dean.view', 'research.view-college', 'research.approve', 'proposal.approve', 'documents.download', 'defenses.view', 'evaluations.view-assigned', 'reports.view'],
        ],
        'dean' => [
            'label' => 'Dean',
            'description' => 'Provides college-level research oversight, approval, and reporting.',
            'user_type' => 'faculty',
            'permissions' => ['dashboards.dean.view', 'research.view-college', 'research.approve', 'proposal.approve', 'documents.download', 'defenses.view', 'evaluations.view-assigned', 'reports.view', 'reports.export'],
        ],
    ],

    /*
    | Permission names are stable application capabilities. Administrators may
    | combine them into custom roles, but cannot create arbitrary permissions.
    */
    'permissions' => [
        'Dashboards' => [
            'dashboards.student.view' => ['label' => 'Open Student Dashboard', 'description' => 'Open the student research workspace.', 'scope' => 'Student workspace'],
            'dashboards.adviser.view' => ['label' => 'Open Adviser Dashboard', 'description' => 'Open the thesis adviser workspace.', 'scope' => 'Adviser workspace'],
            'dashboards.facilitator.view' => ['label' => 'Open Facilitator Dashboard', 'description' => 'Open the research facilitator workspace.', 'scope' => 'Facilitator workspace'],
            'dashboards.panelist.view' => ['label' => 'Open Panel Dashboard', 'description' => 'Open the panel member workspace.', 'scope' => 'Panel workspace'],
            'dashboards.dean.view' => ['label' => 'Open Oversight Dashboard', 'description' => 'Open the chair or dean oversight workspace.', 'scope' => 'College oversight workspace'],
            'dashboards.admin.view' => ['label' => 'Open Admin Dashboard', 'description' => 'Open the system administration workspace.', 'scope' => 'Administration workspace'],
        ],
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
            'classes.serve-as-adviser' => ['label' => 'Serve as Adviser', 'description' => 'Allow assignment as the adviser of a research group.', 'scope' => 'Assigned research groups'],
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
