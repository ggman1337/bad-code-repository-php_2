<?php

return [
    'name' => 'Courier Management System',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
    'jwt_secret' => getenv('APP_JWT_SECRET') ?: 'change-me-in-production',
    'jwt_ttl' => (int)(getenv('APP_JWT_TTL') ?: 28800),
];
