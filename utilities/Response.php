<?php

class Response {
    public static function success($message, $data = null, $statusCode = HTTP_OK) {
        http_response_code($statusCode);
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
    }

    public static function error($message, $data = null, $statusCode = HTTP_INTERNAL_SERVER_ERROR) {
        http_response_code($statusCode);
        self::json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
    }

    public static function created($message, $data = null) {
        self::success($message, $data, HTTP_CREATED);
    }

    public static function badRequest($message, $data = null) {
        self::error($message, $data, HTTP_BAD_REQUEST);
    }

    public static function unauthorized($message, $data = null) {
        self::error($message, $data, HTTP_UNAUTHORIZED);
    }

    public static function forbidden($message, $data = null) {
        self::error($message, $data, HTTP_FORBIDDEN);
    }

    public static function notFound($message, $data = null) {
        self::error($message, $data, HTTP_NOT_FOUND);
    }

    public static function conflict($message, $data = null) {
        self::error($message, $data, HTTP_CONFLICT);
    }

    public static function validationError($errors) {
        self::error('Validation failed', $errors, HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function internalError($message = 'Internal Server Error') {
        self::error($message, null, HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function unavailable($message = 'Service Unavailable') {
        self::error($message, null, HTTP_SERVICE_UNAVAILABLE);
    }

    public static function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function paginated($message, $data, $page, $perPage, $total, $statusCode = HTTP_OK) {
        http_response_code($statusCode);
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => ceil($total / $perPage),
            ],
            'timestamp' => date('c')
        ]);
    }

    public static function raw($data) {
        echo json_encode($data);
        exit;
    }

    public static function header($name, $value) {
        header("$name: $value");
    }
}

// Helper functions for backward compatibility
function success_response($message, $data = null, $statusCode = HTTP_OK) {
    Response::success($message, $data, $statusCode);
}

function error_response($message, $data = null, $statusCode = HTTP_INTERNAL_SERVER_ERROR) {
    Response::error($message, $data, $statusCode);
}

function json_response($data) {
    Response::json($data);
}

?>