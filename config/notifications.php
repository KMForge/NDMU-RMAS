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
];
