<?php

return [
    'verification_key' => env('SIGNATURE_VERIFICATION_KEY'),
    'verification_key_version' => env('SIGNATURE_VERIFICATION_KEY_VERSION', 'v1'),
    'disk' => 'local',
    'specimen_path' => 'signatures',
    'snapshot_path' => 'official_form_signatures',
    'max_file_size_kb' => 2048,
    'normalized_dimensions' => [
        'max_width' => 1200,
        'max_height' => 400,
    ],
];
