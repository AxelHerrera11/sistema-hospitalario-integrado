<?php

return [
    'hospital_database_tenant_id' => env('HOSPITAL_DB_TENANT_ID'),
    'jwt' => [
        'issuer' => env('TENANTS_JWT_ISSUER', 'his-tenants-auth'),
        'audience' => env('TENANTS_JWT_AUDIENCE', 'his-api'),
    ],
    'refresh' => [
        'ttl_seconds' => (int) env('TENANTS_REFRESH_TTL_SECONDS', 604800),
        'cookie_secure' => (bool) env('TENANTS_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'),
    ],
];
