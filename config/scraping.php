<?php

return [
    'candidate_paths' => ['/termine', '/seminare', '/events', '/kurse', '/veranstaltungen', '/workshops'],
    'timeout' => (int) env('SCRAPING_TIMEOUT', 20),
    'max_detail_pages' => (int) env('SCRAPING_MAX_DETAIL_PAGES', 25),
    // Static EUR conversion rates (1 unit of currency = X EUR). Tune as needed.
    'fx' => [
        'EUR' => 1.0,
        'CHF' => 1.05,
        'USD' => 0.92,
        'GBP' => 1.17,
    ],
];
