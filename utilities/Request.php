<?php

class Request {
    private $method;
    private $uri;
    private $query = [];
    private $form = [];
    private $json = null;
    private $files = [];
    private $headers = [];

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET ?? [];
        $this->form = $_POST ?? [];
        $this->files = $_FILES ?? [];
        $this->parseHeaders();
        $this->parseJsonBody();
    }

    private function parseHeaders() {
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('HTTP_', '', $key);
                $header = strtolower(str_replace('_', '-', $header));
                $this->headers[$header] = $value;
            }
        }
    }

    private function parseJsonBody() {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $this->json = json_decode($input, true);
            }
        }
    }

    public function getMethod() {
        return $this->method;
    }

    public function isMethod($method) {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function getUri() {
        return $this->uri;
    }

    public function getPath() {
        return trim($this->uri, '/');
    }

    public function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function query($key = null, $default = null) {
        return $this->getQuery($key, $default);
    }

    public function getForm($key = null, $default = null) {
        if ($key === null) {
            return $this->form;
        }
        return $this->form[$key] ?? $default;
    }

    public function input($key = null, $default = null) {
        if ($key === null) {
            return array_merge($this->form, $this->json ?? []);
        }
        return $this->form[$key] ?? $this->json[$key] ?? $default;
    }

    public function getJsonBody() {
        return $this->json;
    }

    public function json($key = null, $default = null) {
        if ($key === null) {
            return $this->json;
        }
        return $this->json[$key] ?? $default;
    }

    public function all() {
        return array_merge($this->query, $this->form, $this->json ?? []);
    }

    public function get($key, $default = null) {
        return $this->input($key, $default);
    }

    public function has($key) {
        return isset($this->form[$key]) || isset($this->json[$key]) || isset($this->query[$key]);
    }

    public function getHeaders($key = null, $default = null) {
        if ($key === null) {
            return $this->headers;
        }
        $key = strtolower(str_replace('_', '-', $key));
        return $this->headers[$key] ?? $default;
    }

    public function header($key = null, $default = null) {
        return $this->getHeaders($key, $default);
    }

    public function getBearerToken() {
        $header = $this->getHeaders('Authorization');
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getFiles($key = null) {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    public function file($key) {
        return $this->getFiles($key);
    }

    public function hasFile($key) {
        return isset($this->files[$key]);
    }

    public function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getContentType() {
        return $this->getHeaders('Content-Type');
    }

    public function isJson() {
        return strpos($this->getContentType(), 'application/json') !== false;
    }

    public function isForm() {
        return strpos($this->getContentType(), 'application/x-www-form-urlencoded') !== false;
    }
}

?>
