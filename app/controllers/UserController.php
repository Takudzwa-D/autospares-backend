<?php

namespace App\Controllers;

use App\Models\User;

class UserController extends Controller {
    private function serializeUser(array $user): array {
        return [
            'id' => (int)$user['id'],
            'firstName' => $user['FirstName'] ?? '',
            'lastName' => $user['LastName'] ?? '',
            'email' => $user['Email'] ?? '',
            'phoneNumber' => $user['PhoneNumber'] ?? null,
            'address' => $user['Address'] ?? null,
            'city' => $user['City'] ?? null,
            'country' => $user['Country'] ?? null,
            'role' => $user['Role'] ?? USER_ROLE_CUSTOMER,
            'createdAt' => $user['CreatedAt'] ?? null,
            'updatedAt' => $user['UpdatedAt'] ?? null,
        ];
    }

    public function index() {
        $this->requireAdmin();

        try {
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('perPage') ?? 20);
            $role = $this->input('role');
            $offset = ($page - 1) * $perPage;

            $userModel = new User();
            if ($role && in_array($role, [USER_ROLE_ADMIN, USER_ROLE_CUSTOMER], true)) {
                $users = $userModel->getByRole($role, $perPage, $offset);
                $total = $userModel->countByRole($role);
            } else {
                $users = $userModel->getAll($perPage, $offset);
                $total = $userModel->count();
            }

            $users = array_map(fn($user) => $this->serializeUser($user), $users);
            $this->paginated($users, $total, $page, $perPage, 'Users retrieved');
        } catch (\Exception $e) {
            $this->log('error', 'User index failed: ' . $e->getMessage());
            $this->error('Failed to retrieve users', null, 500);
        }
    }

    public function store() {
        $this->requireAdmin();

        try {
            $this->validate([
                'firstName' => 'required|min:2',
                'lastName' => 'required|min:2',
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

            $input = $this->allInput();
            $userModel = new User();
            $email = strtolower(trim($input['email']));

            if ($userModel->findByEmail($email)) {
                $this->conflict('Email already registered');
            }

            $role = $input['role'] ?? USER_ROLE_CUSTOMER;
            if (!in_array($role, [USER_ROLE_ADMIN, USER_ROLE_CUSTOMER], true)) {
                $this->error('Invalid user role', null, 400);
            }

            $userId = $userModel->create([
                'FirstName' => trim($input['firstName']),
                'LastName' => trim($input['lastName']),
                'Email' => $email,
                'Password' => password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
                'Role' => $role,
                'PhoneNumber' => $input['phoneNumber'] ?? null,
                'Address' => $input['address'] ?? null,
                'City' => $input['city'] ?? null,
                'Country' => $input['country'] ?? null,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'UpdatedAt' => date('Y-m-d H:i:s'),
            ]);

            $this->created('User created successfully', $this->serializeUser($userModel->findById((int)$userId)));
        } catch (\Exception $e) {
            $this->log('error', 'User store failed: ' . $e->getMessage());
            $this->error('Failed to create user', null, 500);
        }
    }

    public function update($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $userModel = new User();
            $existing = $userModel->findById($id);

            if (!$existing) {
                $this->notFound('User not found');
            }

            $input = $this->allInput();
            $updateData = [];

            if (isset($input['firstName'])) {
                $updateData['FirstName'] = trim($input['firstName']);
            }
            if (isset($input['lastName'])) {
                $updateData['LastName'] = trim($input['lastName']);
            }
            if (isset($input['email'])) {
                $email = strtolower(trim($input['email']));
                if ($userModel->emailExists($email, $id)) {
                    $this->conflict('Email already registered');
                }
                $updateData['Email'] = $email;
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
            if (isset($input['role'])) {
                if (!in_array($input['role'], [USER_ROLE_ADMIN, USER_ROLE_CUSTOMER], true)) {
                    $this->error('Invalid user role', null, 400);
                }
                $updateData['Role'] = $input['role'];
            }
            if (!empty($input['password'])) {
                $updateData['Password'] = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            }

            if (empty($updateData)) {
                $this->error('No user updates provided', null, 400);
            }

            $updateData['UpdatedAt'] = date('Y-m-d H:i:s');
            $userModel->updateUser($id, $updateData);

            $this->success('User updated successfully', $this->serializeUser($userModel->findById($id)));
        } catch (\Exception $e) {
            $this->log('error', 'User update failed: ' . $e->getMessage());
            $this->error('Failed to update user', null, 500);
        }
    }

    public function destroy($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            if ($id === (int)$this->userId()) {
                $this->conflict('You cannot delete the currently logged in admin account');
            }

            $userModel = new User();
            if (!$userModel->findById($id)) {
                $this->notFound('User not found');
            }

            $userModel->deleteUser($id);
            $this->success('User deleted successfully');
        } catch (\Exception $e) {
            $this->log('error', 'User delete failed: ' . $e->getMessage());
            $this->error('Failed to delete user', null, 500);
        }
    }
}
