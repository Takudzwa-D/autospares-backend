<?php


class Auth {
    private $user = null;
    private $token = null;

    public function __construct() {
        $this->loadFromToken();
    }

    private function loadFromToken() {
        $request = new Request();
        $token = $request->getBearerToken();

        if ($token && $this->verifyToken($token)) {
            $this->token = $token;
        }
    }

    public function generateToken($payload, $expiresIn = null) {
        $expiresIn = $expiresIn ?? JWT_EXPIRY;

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;

        $header_encoded = $this->base64urlEncode(json_encode($header));
        $payload_encoded = $this->base64urlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "$header_encoded.$payload_encoded",
            JWT_SECRET,
            true
        );
        $signature_encoded = $this->base64urlEncode($signature);

        return "$header_encoded.$payload_encoded.$signature_encoded";
    }

    public function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

        // Signature verification
        $signature = hash_hmac(
            'sha256',
            "$header_encoded.$payload_encoded",
            JWT_SECRET,
            true
        );
        $signature_encoded_check = $this->base64urlEncode($signature);

        if (!hash_equals($signature_encoded_check, $signature_encoded)) {
            return false;
        }

        // Decode payload
        $payload = json_decode($this->base64urlDecode($payload_encoded), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        $this->user = $payload;
        return $payload;
    }

    public function getCurrentUser() {
        return $this->user;
    }

    public function isAuthenticated() {
        return $this->user !== null;
    }

    public function getUserId() {
        return $this->user['id'] ?? null;
    }

    public function hasRole($role) {
        return ($this->user['role'] ?? null) === $role;
    }

    public function isAdmin() {
        return $this->hasRole(USER_ROLE_ADMIN);
    }

    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required',
                'timestamp' => date('c')
            ]);
            exit;
        }
        return $this->user;
    }

    public function requireAdmin() {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Admin access required',
                'timestamp' => date('c')
            ]);
            exit;
        }
        return $this->user;
    }

    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    private function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}

?>
