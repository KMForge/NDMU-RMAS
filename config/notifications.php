<?php

return [
    'database' => true,
    'mail' => (bool) env('NOTIFICATIONS_MAIL_ENABLED', true),
    'realtime' => (bool) env('SUPABASE_REALTIME_ENABLED', false),

    /*
    | Only these internal named routes may be resolved from persisted
    | notification data. Values are the accepted route/query parameters.
    */
    'safe_destinations' => [
        'student.dashboard' => ['tab'],
        'adviser.dashboard' => ['tab'],
        'facilitator.dashboard' => ['tab'],
        'panelist.dashboard' => ['tab'],
        'dean.dashboard' => ['tab'],
        'admin.dashboard' => ['tab'],
        'official-forms.workspace.show' => ['instance'],
    ],

    'workspace_labels' => [
        'admin' => 'System Administrator',
        'facilitator' => 'Research Facilitator',
        'dean' => 'College Dean',
        'adviser' => 'Thesis Adviser',
        'panelist' => 'Panel Member',
        'student' => 'Student Researcher',
    ],

    'acting_as_workspaces' => [
        'system_administrator' => 'admin',
        'administrator' => 'admin',
        'research_facilitator' => 'facilitator',
        'research_instructor' => 'facilitator',
        'program_coordinator' => 'facilitator',
        'college_dean' => 'dean',
        'dean' => 'dean',
        'thesis_adviser' => 'adviser',
        'adviser' => 'adviser',
        'panel_member' => 'panelist',
        'panel_chairperson' => 'panelist',
        'panelist' => 'panelist',
        'title_panel_chairperson' => 'panelist',
        'student_researcher' => 'student',
    ],
];
