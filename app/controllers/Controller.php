<?php

namespace App\Controllers;

use App\Models\Model;

/**
 * Base Controller
 * Provides common functionality to all controllers
 */
class Controller {
    protected $user = null;
    protected $request;
    protected $validator;

    /**
     * Constructor
     */
    public function __construct() {
        global $request, $validator;
        $this->request = $request;
        $this->validator = $validator;
    }

    /**
     * Require authentication
     * Returns current user or exits with 401
     */
    protected function requireAuth() {
        global $auth;
        if (!$auth->isAuthenticated()) {
            $this->unauthorized('Authentication required');
        }
        $this->user = $auth->getCurrentUser();
        return $this->user;
    }

    /**
     * Require admin role
     * Returns current user if admin, exits with 403 otherwise
     */
    protected function requireAdmin() {
        $this->requireAuth();
        $role = $this->user['role'] ?? $this->user['Role'] ?? null;
        if (!$this->user || $role !== 'Admin') {
            $this->forbidden('Admin access required');
        }
        return $this->user;
    }

    /**
     * Validate input
     * @param array $rules Validation rules
     * @return bool
     */
    protected function validate($rules) {
        $data = array_merge(
            $this->request->getForm(),
            $this->request->getJsonBody() ?? []
        );

        if (!$this->validator->validate($data, $rules)) {
            $this->validationError($this->validator->getErrors());
        }
        return true;
    }

    /**
     * Get input value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function input($key, $default = null) {
        $query = $this->request->getQuery();
        $form = $this->request->getForm();
        $json = $this->request->getJsonBody() ?? [];
        return $query[$key] ?? $form[$key] ?? $json[$key] ?? $default;
    }

    /**
     * Get all input
     * @return array
     */
    protected function allInput() {
        return array_merge(
            $this->request->getQuery(),
            $this->request->getForm(),
            $this->request->getJsonBody() ?? []
        );
    }

    /**
     * Get current user ID
     * @return int|null
     */
    protected function userId() {
        return $this->user['id'] ?? null;
    }

    /**
     * Send success response
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     */
    protected function success($message, $data = null, $statusCode = 200) {
        \Response::success($message, $data, $statusCode);
        exit;
    }

    /**
     * Send error response
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     */
    protected function error($message, $data = null, $statusCode = 500) {
        \Response::error($message, $data, $statusCode);
        exit;
    }

    /**
     * Send created response
     * @param string $message
     * @param mixed $data
     */
    protected function created($message, $data = null) {
        \Response::created($message, $data);
        exit;
    }

    /**
     * Send not found response
     * @param string $message
     */
    protected function notFound($message = 'Resource not found') {
        \Response::notFound($message);
        exit;
    }

    /**
     * Send forbidden response
     * @param string $message
     */
    protected function forbidden($message = 'Access forbidden') {
        \Response::forbidden($message);
        exit;
    }

    /**
     * Send unauthorized response
     * @param string $message
     */
    protected function unauthorized($message = 'Unauthorized') {
        \Response::unauthorized($message);
        exit;
    }

    /**
     * Send validation error response
     * @param array $errors
     */
    protected function validationError($errors = []) {
        \Response::validationError($errors);
        exit;
    }

    /**
     * Send conflict response
     * @param string $message
     */
    protected function conflict($message) {
        \Response::conflict($message);
        exit;
    }

    /**
     * Send paginated response
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $perPage
     * @param string $message
     */
    protected function paginated($data, $total, $page, $perPage, $message = 'Success') {
        \Response::paginated($message, $data, $page, $perPage, $total);
        exit;
    }

    /**
     * Log message
     * @param string $level
     * @param string $message
     */
    protected function log($level = 'info', $message = '') {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] $level: $message");
    }
}
