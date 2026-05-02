<?php

// Environment Setup
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');
define('APP_NAME', 'AutoSpares Backend');
define('APP_VERSION', '2.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/ecommerce_backend');
define('FRONTEND_URL', getenv('FRONTEND_URL') ?: 'https://autospares-alpha.vercel.app/');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'AutoSpares');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// JWT Configuration (Different secret than electronics)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'ftwegjadvblhfdewj6583279gyusdackbiweaiu237624387gif3g7823g8fiqgfkt382');
define('JWT_EXPIRY', getenv('JWT_EXPIRY') ?: 86400); // 24 hours

// Password Configuration
define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_MAX_LENGTH', 255);
define('BCRYPT_COST', 10);

// Pagination Defaults
define('DEFAULT_PER_PAGE', 15);
define('DEFAULT_PAGE', 1);

// User Roles (Automotive-specific terminology)
define('USER_ROLE_ADMIN', 'Admin');
define('USER_ROLE_CUSTOMER', 'Customer');
define('USER_ROLE_GUEST', 'Guest');
define('VALID_ROLES', [USER_ROLE_ADMIN, USER_ROLE_CUSTOMER, USER_ROLE_GUEST]);

// Order status values 
define('ORDER_STATUS_PENDING', 'Pending');
define('ORDER_STATUS_COMPLETED', 'Completed');
define('ORDER_STATUS_CANCELED', 'Canceled');
define('ORDER_STATUS_FAILED', 'Failed');
define('ORDER_STATUS_CANCELLED', ORDER_STATUS_CANCELED);
define('VALID_ORDER_STATUSES', [
    ORDER_STATUS_PENDING,
    ORDER_STATUS_COMPLETED,
    ORDER_STATUS_CANCELED,
    ORDER_STATUS_FAILED,
]);

// Payment status values 
define('PAYMENT_STATUS_PENDING', 'Pending');
define('PAYMENT_STATUS_COMPLETED', 'Completed');
define('PAYMENT_STATUS_FAILED', 'Failed');
define('VALID_PAYMENT_STATUSES', [
    PAYMENT_STATUS_PENDING,
    PAYMENT_STATUS_COMPLETED,
    PAYMENT_STATUS_FAILED,
]);

define('PAYMENT_METHODS', [
    'credit_card',
    'debit_card',
    'mobile_money',
    'bank_transfer',
    'cash_on_delivery',
]);

// Paynow integration
define('PAYNOW_INTEGRATION_ID', getenv('PAYNOW_INTEGRATION_ID') ?: '');
define('PAYNOW_INTEGRATION_KEY', getenv('PAYNOW_INTEGRATION_KEY') ?: '');
define('PAYNOW_INITIATE_URL', getenv('PAYNOW_INITIATE_URL') ?: 'https://www.paynow.co.zw/interface/initiatetransaction');
define('PAYNOW_RETURN_URL', getenv('PAYNOW_RETURN_URL') ?: rtrim(FRONTEND_URL, '/') . '/payment/return');
define('PAYNOW_RESULT_URL', getenv('PAYNOW_RESULT_URL') ?: rtrim(APP_URL, '/') . '/api/payments/result');

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_CONFLICT', 409);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_INTERNAL_SERVER_ERROR', 500);
define('HTTP_SERVICE_UNAVAILABLE', 503);

// CORS Configuration
define('CORS_ENABLED', true);
define('CORS_ORIGINS', [
    'http://localhost:3000',
    'https://autospares-alpha.vercel.app/',
    'http://localhost:8080',
    'http://127.0.0.1:3000',
    getenv('FRONTEND_URL') ?: null,
]);
define('CORS_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);
define('CORS_HEADERS', ['Content-Type', 'Authorization', 'Accept']);
define('CORS_CREDENTIALS', true);
define('CORS_MAX_AGE', 86400);

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_MAX_SIZE', 5242880); 
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// API Configuration
define('API_VERSION', 'v1');
define('API_PREFIX', '/api/' . API_VERSION);
define('API_RATE_LIMIT', 100); 
define('API_RATE_WINDOW', 60); 

// Feature Flags
define('FEATURE_PRODUCT_REVIEWS', true);
define('FEATURE_WISHLIST', true);
define('FEATURE_ADVANCED_SEARCH', true);
define('FEATURE_INVENTORY_MANAGEMENT', true);
define('FEATURE_MULTI_BRAND_SUPPORT', true);

// Logging
define('LOG_ENABLED', APP_DEBUG);
define('LOG_DIR', __DIR__ . '/../logs');
define('LOG_LEVEL', APP_DEBUG ? 'debug' : 'error');

// Cache Configuration (if needed in future)
define('CACHE_ENABLED', false);
define('CACHE_DRIVER', 'file');
define('CACHE_TTL', 3600);

// Timezone
define('APP_TIMEZONE', 'UTC');
date_default_timezone_set(APP_TIMEZONE);

?>
