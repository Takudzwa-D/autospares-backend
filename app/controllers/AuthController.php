<?php

namespace App\Controllers;

use App\Models\User;

/**
 * AuthController
 * Handles authentication: register, login, profile, logout
 * Different implementation than electronics backend
 */
class AuthController extends Controller {
    private function serializeUser(array $user, ?string $token = null): array {
        $serialized = [
            'id' => (int)$user['id'],
            'userId' => (int)$user['id'],
            'email' => $user['Email'],
            'firstName' => $user['FirstName'],
            'lastName' => $user['LastName'],
            'phoneNumber' => $user['PhoneNumber'] ?? null,
            'address' => $user['Address'] ?? null,
            'city' => $user['City'] ?? null,
            'country' => $user['Country'] ?? null,
            'role' => $user['Role'],
            'createdAt' => $user['CreatedAt'] ?? null,
            'updatedAt' => $user['UpdatedAt'] ?? null,
        ];

        if ($token !== null) {
            $serialized['token'] = $token;
        }

        return $serialized;
    }

    /**
     * POST /api/auth/register
     */
    public function register() {
        try {
            // Get JSON data differently - direct parsing approach
            $input = $this->allInput();

            // Validate all required fields exist
            $this->validate([
                'firstName' => 'required|min:2',
                'lastName' => 'required|min:2',
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

            $userModel = new User();

            // Check email uniqueness using model method
            if ($userModel->findByEmail($input['email'])) {
                $this->conflict('Email already registered');
            }

            // Create user with only necessary fields
            $userId = $userModel->create([
                'FirstName' => trim($input['firstName']),
                'LastName' => trim($input['lastName']),
                'Email' => strtolower(trim($input['email'])),
                'Password' => password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 10]),
                'Role' => 'Customer',
                'PhoneNumber' => $input['phoneNumber'] ?? null,
                'Address' => $input['address'] ?? null,
                'City' => $input['city'] ?? null,
                'Country' => $input['country'] ?? null,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'UpdatedAt' => date('Y-m-d H:i:s'),
            ]);

            if (!$userId) {
                $this->error('Registration failed', null, 500);
            }

            // Get created user
            $user = $userModel->findById($userId);

            // Generate JWT token
            global $auth;
            $token = $auth->generateToken([
                'id' => $user['id'],
                'email' => $user['Email'],
                'firstName' => $user['FirstName'],
                'lastName' => $user['LastName'],
                'role' => $user['Role'],
            ]);

            $this->created('User registered successfully', $this->serializeUser($user, $token));
        } catch (\Exception $e) {
            $this->log('error', 'Register failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Registration failed: ' . $e->getMessage()) : 'Registration failed';
            $this->error($message, null, 500);
        }
    }

    /**
     * POST /api/auth/login
     */
    public function login() {
        try {
            $input = $this->allInput();

            $this->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $userModel = new User();
            $user = $userModel->findByEmail(strtolower($input['email']));

            // Generic error message for security
            if (!$user || !password_verify($input['password'], $user['Password'])) {
                $this->error('Invalid credentials', null, 401);
            }

            // Generate token
            global $auth;
            $token = $auth->generateToken([
                'id' => $user['id'],
                'email' => $user['Email'],
                'firstName' => $user['FirstName'],
                'lastName' => $user['LastName'],
                'role' => $user['Role'],
            ]);

            $this->success('Login successful', $this->serializeUser($user, $token));
        } catch (\Exception $e) {
            $this->log('error', 'Login failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Login failed: ' . $e->getMessage()) : 'Login failed';
            $this->error($message, null, 500);
        }
    }

    /**
     * GET /api/auth/profile
     */
    public function profile() {
        $this->requireAuth();

        try {
            $userModel = new User();
            $user = $userModel->findById($this->userId());

            if (!$user) {
                $this->notFound('User not found');
            }

            $this->success('Profile retrieved', $this->serializeUser($user));
        } catch (\Exception $e) {
            $this->log('error', 'Profile fetch failed: ' . $e->getMessage());
            $this->error('Failed to fetch profile', null, 500);
        }
    }

    /**
     * PUT /api/auth/profile
     */
    public function updateProfile() {
        $this->requireAuth();

        try {
            $input = $this->allInput();
            $updateData = [];

            if (isset($input['firstName'])) {
                $updateData['FirstName'] = trim($input['firstName']);
            }
            if (isset($input['lastName'])) {
                $updateData['LastName'] = trim($input['lastName']);
            }
            if (isset($input['phoneNumber'])) {
                $updateData['PhoneNumber'] = trim((string)$input['phoneNumber']);
            }
            if (isset($input['address'])) {
                $updateData['Address'] = trim((string)$input['address']);
            }
            if (isset($input['city'])) {
                $updateData['City'] = trim((string)$input['city']);
            }
            if (isset($input['country'])) {
                $updateData['Country'] = trim((string)$input['country']);
            }

            if (empty($updateData)) {
                $this->error('No profile changes provided', null, 400);
            }

            $userModel = new User();
            $userModel->updateUser($this->userId(), $updateData);
            $user = $userModel->findById($this->userId());

            $this->success('Profile updated successfully', $this->serializeUser($user));
        } catch (\Exception $e) {
            $this->log('error', 'Profile update failed: ' . $e->getMessage());
            $this->error('Failed to update profile', null, 500);
        }
    }

    /**
     * POST /api/auth/logout
     */
    public function logout() {
        $this->requireAuth();
        // Placeholder for token blacklist implementation
        $this->success('Logout successful');
    }
}
