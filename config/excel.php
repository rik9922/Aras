<?php

return [
    // Keep cell caching bounded and let the XLSX writer use temporary XML files.
    'cache' => [
        'driver' => 'batch',
        'batch' => ['memory_limit' => 6000],
        'illuminate' => ['store' => 'exports'],
        'default_ttl' => 10800,
    ],
];
