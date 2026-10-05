<?php

return [
    'slow_request_ms' => max(100, (int) env('SLOW_REQUEST_MS', 750)),
];
