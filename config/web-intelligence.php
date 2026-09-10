<?php

return [
    'enabled' => env('WEB_INTELLIGENCE_ENABLED', true),
    // Geolocation remains disabled until a trusted provider is configured.
    'trust_cloudflare_country' => env('WEB_INTELLIGENCE_TRUST_CLOUDFLARE_COUNTRY', false),
    // Null means keep the data until the business defines a retention policy.
    'raw_retention_days' => env('WEB_INTELLIGENCE_RAW_RETENTION_DAYS'),
    'security_retention_days' => env('WEB_INTELLIGENCE_SECURITY_RETENTION_DAYS'),
    'audit_retention_days' => env('WEB_INTELLIGENCE_AUDIT_RETENTION_DAYS'),
    'excluded_paths' => ['admin', 'admin/*', 'dashboard', 'up', 'build/*', 'storage/*'],
];
