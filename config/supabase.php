<?php

return [
    'url' => env('SUPABASE_URL'),
    'anon_key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    'timeout' => (int) env('SUPABASE_HTTP_TIMEOUT', 30),

    'storage' => [
        'default_bucket' => env('SUPABASE_STORAGE_BUCKET', 'research-manuscripts'),
        'private_buckets' => [
            'research-proposals',
            'research-manuscripts',
            'research-revisions',
            'defense-documents',
            'evaluation-sheets',
            'archived-research',
            'profile-photos',
        ],
        'signed_url_ttl' => (int) env('SUPABASE_SIGNED_URL_TTL', 300),
    ],

    'realtime' => [
        'enabled' => (bool) env('SUPABASE_REALTIME_ENABLED', false),
        'endpoint' => env('SUPABASE_REALTIME_ENDPOINT'),
    ],
];
