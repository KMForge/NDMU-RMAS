<?php

return [
    'database' => true,
    'mail' => (bool) env('NOTIFICATIONS_MAIL_ENABLED', true),
    'realtime' => (bool) env('SUPABASE_REALTIME_ENABLED', false),
];
