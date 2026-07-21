<?php

return [
    'cache_ttl' => (int) env('ANALYTICS_CACHE_TTL', 900),
    'max_export_rows' => (int) env('ANALYTICS_MAX_EXPORT_ROWS', 10000),
];
