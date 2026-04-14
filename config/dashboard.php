<?php

return [
    'cache' => [
        'enabled' => env('DASHBOARD_CACHE_ENABLED', true),
        'ttl_seconds' => (int) env('DASHBOARD_CACHE_TTL_SECONDS', 600),
    ],

    'warm' => [
        'enabled' => env('DASHBOARD_CACHE_WARM_ENABLED', true),
    ],
];
