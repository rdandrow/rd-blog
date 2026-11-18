<?php

return [
    'cache' => [
        'enabled' => env('DASHBOARD_CACHE_ENABLED', true),
        'ttl_seconds' => (int) env('DASHBOARD_CACHE_TTL_SECONDS', 600),
    ],

    'warm' => [
        'enabled' => env('DASHBOARD_CACHE_WARM_ENABLED', true),
    ],

    'views' => [
        // Days of blog_post_views history to keep; null keeps everything.
        // Pruning lowers lifetime view totals shown on the dashboard.
        'retention_days' => env('DASHBOARD_VIEWS_RETENTION_DAYS') !== null
            ? (int) env('DASHBOARD_VIEWS_RETENTION_DAYS')
            : null,
    ],
];
