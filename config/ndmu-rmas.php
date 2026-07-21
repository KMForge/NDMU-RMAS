<?php

return [
    'institution' => 'Notre Dame of Marbel University',
    'document' => [
        'max_upload_kilobytes' => (int) env('DOCUMENT_MAX_UPLOAD_KB', 20480),
        'allowed_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],
    'accounts' => [
        'require_approval' => (bool) env('ACCOUNT_APPROVAL_REQUIRED', true),
    ],
    'bootstrap_admin' => [
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
