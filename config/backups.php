<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'system-backups'),
    'pg_dump_binary' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
    'pg_restore_binary' => env('BACKUP_PG_RESTORE_BINARY', 'pg_restore'),
    'pg_dump_docker_container' => env('BACKUP_PG_DUMP_DOCKER_CONTAINER'),
    'docker_binary' => env('BACKUP_DOCKER_BINARY', 'docker'),
    'include_private_files' => env('BACKUP_INCLUDE_PRIVATE_FILES', true),
    'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 600),
    'restore_request_timeout_seconds' => (int) env('BACKUP_RESTORE_REQUEST_TIMEOUT_SECONDS', 1800),
    'upload_timeout_minutes' => (int) env('BACKUP_UPLOAD_TIMEOUT_MINUTES', 30),
    'chunk_upload_kilobytes' => (int) env('BACKUP_UPLOAD_CHUNK_KB', 8192),
    'default_import_limit_mb' => (int) env('BACKUP_MAX_IMPORT_MB', 10),
    'max_import_limit_mb' => (int) env('BACKUP_MAX_IMPORT_CEILING_MB', 1024),
    'max_uncompressed_mb' => (int) env('BACKUP_MAX_UNCOMPRESSED_MB', 5120),
];
