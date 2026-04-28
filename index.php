<?php

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');

// Load Configuration
require_once __DIR__ . '/config/app.php';

// Load Utility Classes
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Request.php';
require_once __DIR__ . '/utils/Validator.php';
require_once __DIR__ . '/utils/Auth.php';

// PSR-4 Autoloader for namespaced classes
spl_autoload_register(function ($class) {
    // Project namespace prefix
    $prefix = 'App\\';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/app/';
    
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $relative_path = str_replace('\\', '/', $relative_class);
    $file = $base_dir . $relative_path . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    }

    // Fallback for lowercase top-level directories used in this project 
    $parts = explode('/', $relative_path);
    if (!empty($parts)) {
        $parts[0] = strtolower($parts[0]);
        $fallback_file = $base_dir . implode('/', $parts) . '.php';
        if (file_exists($fallback_file)) {
            require $fallback_file;
        }
    }
});

// Load core classes 
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';

// Load CORS configuration
require_once __DIR__ . '/config/cors.php';

// Initialize utilities as globals for access in controllers
$GLOBALS['request'] = new Request();
$GLOBALS['validator'] = new Validator();
$GLOBALS['auth'] = new Auth();

// Set CORS headers
$allowed_origins = array_filter(CORS_ORIGINS);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (CORS_ENABLED) {
    if (!empty($allowed_origins) && in_array($origin, $allowed_origins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } elseif (empty($allowed_origins)) {
        header('Access-Control-Allow-Origin: *');
    } elseif (in_array('http://localhost:5173', $allowed_origins, true)) {
        // Safe local-dev fallback for Vite frontend when no Origin header is present.
        header('Access-Control-Allow-Origin: http://localhost:5173');
    }
}
header('Access-Control-Allow-Methods: ' . implode(', ', CORS_METHODS));
header('Access-Control-Allow-Headers: ' . implode(', ', CORS_HEADERS));
header('Access-Control-Allow-Credentials: ' . (CORS_CREDENTIALS ? 'true' : 'false'));
header('Access-Control-Max-Age: ' . CORS_MAX_AGE);
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Initialize Router and register all routes
$router = new Router();

// Auth Routes
$router->post('api/auth/register', 'AuthController@register');
$router->post('api/auth/login', 'AuthController@login');
$router->post('api/auth/logout', 'AuthController@logout');
$router->get('api/auth/profile', 'AuthController@profile');
$router->put('api/auth/profile', 'AuthController@updateProfile');

// Product Routes
$router->get('api/products', 'ProductController@index');
$router->get('api/products/search', 'ProductController@search');
$router->get('api/products/:id', 'ProductController@show');
$router->post('api/products', 'ProductController@store');
$router->post('api/products/:id/image', 'ProductController@uploadImage');
$router->put('api/products/:id', 'ProductController@update');
$router->delete('api/products/:id', 'ProductController@destroy');

// Category Routes
$router->get('api/categories', 'CategoryController@index');
$router->get('api/categories/search', 'CategoryController@search');
$router->get('api/categories/:id', 'CategoryController@show');
$router->post('api/categories', 'CategoryController@store');
$router->put('api/categories/:id', 'CategoryController@update');
$router->delete('api/categories/:id', 'CategoryController@destroy');

// Sub-category Routes
$router->get('api/sub-categories', 'SubCategoryController@index');
$router->get('api/sub-categories/:id', 'SubCategoryController@show');
$router->post('api/sub-categories', 'SubCategoryController@store');
$router->put('api/sub-categories/:id', 'SubCategoryController@update');
$router->delete('api/sub-categories/:id', 'SubCategoryController@destroy');

// Brand and Model Routes
$router->get('api/brands', 'BrandController@index');
$router->get('api/brands/:id', 'BrandController@show');
$router->post('api/brands', 'BrandController@store');
$router->put('api/brands/:id', 'BrandController@update');
$router->delete('api/brands/:id', 'BrandController@destroy');

$router->get('api/models', 'CarModelController@index');
$router->get('api/models/:id', 'CarModelController@show');
$router->post('api/models', 'CarModelController@store');
$router->put('api/models/:id', 'CarModelController@update');
$router->delete('api/models/:id', 'CarModelController@destroy');

// Order Routes
$router->get('api/orders', 'OrderController@index');
$router->get('api/orders/my', 'OrderController@myOrders');
$router->get('api/orders/stats', 'OrderController@stats');
$router->get('api/orders/:id', 'OrderController@show');
$router->post('api/orders', 'OrderController@store');
$router->put('api/orders/:id/status', 'OrderController@updateStatus');
$router->delete('api/orders/:id', 'OrderController@destroy');

// Payment Routes
$router->get('api/payments/status', 'PaymentController@status');
$router->post('api/payments/initiate', 'PaymentController@initiate');
$router->post('api/payments/verify', 'PaymentController@verify');
$router->get('api/payments/result', 'PaymentController@result');
$router->post('api/payments/result', 'PaymentController@result');
$router->get('api/payments', 'PaymentController@index');
$router->get('api/payments/stats', 'PaymentController@stats');
$router->post('api/payments/webhook', 'PaymentController@webhook');

// User Routes
$router->get('api/users', 'UserController@index');
$router->post('api/users', 'UserController@store');
$router->put('api/users/:id', 'UserController@update');
$router->delete('api/users/:id', 'UserController@destroy');

// Dispatch request
try {
    $router->dispatch();
} catch (\Throwable $e) {
    if (APP_DEBUG) {
        error_response('An error occurred: ' . $e->getMessage(), null, HTTP_INTERNAL_SERVER_ERROR);
    } else {
        error_response('Internal Server Error', null, HTTP_INTERNAL_SERVER_ERROR);
    }
}

?>
