<?php

return [
    'institution' => 'Notre Dame of Marbel University',
    'document' => [
        'max_upload_kilobytes' => (int) env('DOCUMENT_MAX_UPLOAD_KB', 10240),
        'storage_disk' => env('DOCUMENT_STORAGE_DISK', 'local'),
        'storage_directory' => env('DOCUMENT_STORAGE_DIRECTORY', 'documents'),
        'allowed_types' => [
            'pdf' => [
                'application/pdf',
            ],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/x-zip-compressed',
            ],
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
