<?php

return [
    'minimum_advance_minutes' => (int) env('CONSULTATION_MIN_ADVANCE_MINUTES', 360),
    'maximum_advance_days' => (int) env('CONSULTATION_MAX_ADVANCE_DAYS', 90),
    'allowed_durations' => [30, 45, 60],
    'default_duration' => 60,
];
