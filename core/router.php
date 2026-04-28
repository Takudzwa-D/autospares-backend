<?php


//   Router Handles HTTP routing 

class Router {
    private $routes = [];
    private $currentMethod = null;
    private $currentPath = null;
    private $parameters = [];
    private $notFoundHandler = null;
    private $middlewares = [];

  
    public function __construct() { 
        $this->currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->currentPath = $this->parsePath();
    }

    /**
     * Parse request path
     * @return string
     */
    private function parsePath() {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $basePath = '/ecommerce_backend';
        
        // Remove base path
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        return trim($uri, '/');
    }

    /**
     * Register GET route
     * @param string $pattern
     * @param callable|string $handler
     * @return self
     */
    public function get($pattern, $handler) {
        return $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Register POST route
     * @param string $pattern
     * @param callable|string $handler
     * @return self
     */
    public function post($pattern, $handler) {
        return $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Register PUT route
     * @param string $pattern
     * @param callable|string $handler
     * @return self
     */
    public function put($pattern, $handler) {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    /**
     * Register DELETE route
     * @param string $pattern
     * @param callable|string $handler
     * @return self
     */
    public function delete($pattern, $handler) {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Add route to collection
     * @param string $method
     * @param string $pattern
     * @param callable|string $handler
     * @return self
     */
    private function addRoute($method, $pattern, $handler) {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $this->middlewares
        ];
        $this->middlewares = [];
        return $this;
    }

    /**
     * Register middleware
     * @param callable $middleware
     * @return self
     */
    public function middleware($middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Handle 404 not found
     * @param callable $handler
     * @return self
     */
    public function notFound($handler) {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Match route and dispatch
     * @return mixed
     */
    public function dispatch() {
        // Try to match route
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route)) {
                // Execute middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_callable($middleware)) {
                        call_user_func($middleware);
                    }
                }

                // Execute handler 
                if (is_callable($route['handler'])) {
                    return call_user_func($route['handler'], ...$this->parameters);
                } elseif (is_string($route['handler'])) {
                    return $this->executeControllerAction($route['handler']);
                }
            }
        }

        // Route not found
        if ($this->notFoundHandler) {
            return call_user_func($this->notFoundHandler);
        }

        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found',
            'timestamp' => date('c')
        ]);
    }

    /**
     * Match route pattern with current request
     * @param array $route
     * @return bool
     */
    private function matchRoute($route) {
        if ($route['method'] !== $this->currentMethod) {
            return false;
        }

        // Convert pattern to regex and extract parameters
        $pattern = $route['pattern'];
        $regex = $this->patternToRegex($pattern);

        if (preg_match($regex, $this->currentPath, $matches)) {

            // Extracting named parameters
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $this->parameters[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Convert pattern to regex
     * Pattern format: 'api/products/:id/reviews/:reviewId'
     * @param string $pattern
     * @return string
     */
    private function patternToRegex($pattern) {
        $pattern = str_replace('/', '\/', $pattern);
        $regex = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '(?P<$1>[^\/]+)', $pattern);
        return '/^' . $regex . '$/';
    }

    /**
     * Execute controller action
     * @param string $handler Format: "ControllerName@methodName"
     * @return mixed
     */
    private function executeControllerAction($handler) {
        list($controllerName, $actionName) = explode('@', $handler);
        $controllerClass = "\\App\\Controllers\\" . $controllerName;

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found: ' . $controllerClass,
                'timestamp' => date('c')
            ]);
            return;
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $actionName)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Action not found: ' . $actionName,
                'timestamp' => date('c')
            ]);
            return;
        }

        return call_user_func_array([$controller, $actionName], array_values($this->parameters));
    }

 //Set CORS header for cross-origin requests
    public function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 3600');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Get all registered routes count
     * @return int
     */
    public function getRouteCount() {
        return count($this->routes);
    }
}
