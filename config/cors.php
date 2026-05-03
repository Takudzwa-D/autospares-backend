<?php


if (!defined('CORS_ENABLED')) {
    define('CORS_ENABLED', true);
}

if (!defined('CORS_ORIGINS')) {
    define('CORS_ORIGINS', [
        'http://localhost:3000',
        'https://autospares-alpha.vercel.app/',
        'http://127.0.0.1:3000',
        'https://autospares-alpha.vercel.app',
    ]);
}

if (!defined('CORS_METHODS')) {
    define('CORS_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);
}

if (!defined('CORS_HEADERS')) {
    define('CORS_HEADERS', ['Content-Type', 'Authorization', 'Accept']);
}

if (!defined('CORS_CREDENTIALS')) {
    define('CORS_CREDENTIALS', true);
}

if (!defined('CORS_MAX_AGE')) {
    define('CORS_MAX_AGE', 86400);
}
