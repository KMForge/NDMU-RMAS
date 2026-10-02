<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'system-backups'),
    'pg_dump_binary' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
    'pg_dump_docker_container' => env('BACKUP_PG_DUMP_DOCKER_CONTAINER'),
    'include_private_files' => env('BACKUP_INCLUDE_PRIVATE_FILES', true),
    'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 600),
];
