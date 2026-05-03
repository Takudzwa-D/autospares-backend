<?php

namespace App\Models;

/**
 * User Model - Automotive Backend
 * Handles user authentication and account management
 * Different implementation than electronics backend
 */
class User extends Model {
    protected $table = 'Users';

    private function normalizeUser(?array $user): ?array {
        if (!$user) {
            return null;
        }

        $map = [
            'firstname' => 'FirstName',
            'lastname' => 'LastName',
            'role' => 'Role',
            'email' => 'Email',
            'phonenumber' => 'PhoneNumber',
            'address' => 'Address',
            'city' => 'City',
            'country' => 'Country',
            'password' => 'Password',
            'createdat' => 'CreatedAt',
            'updatedat' => 'UpdatedAt',
        ];

        foreach ($map as $source => $target) {
            if (array_key_exists($source, $user) && !array_key_exists($target, $user)) {
                $user[$target] = $user[$source];
            }
        }

        return $user;
    }

    private function normalizeUsers(array $users): array {
        return array_map(fn($user) => $this->normalizeUser($user), $users);
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        return $this->normalizeUser($this->getRow(
            "SELECT * FROM {$this->table} WHERE Email = ? LIMIT 1",
            [$email]
        ));
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        return $this->normalizeUser($this->getRow(
            "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        ));
    }

    /**
     * Get all users with pagination
     */
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT id, FirstName, LastName, Email, PhoneNumber, Address, City, Country, Role, CreatedAt, UpdatedAt 
                FROM {$this->table} ORDER BY CreatedAt DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->normalizeUsers($this->getRows($sql, [$limit, $offset]));
        }
        
        return $this->normalizeUsers($this->getRows($sql));
    }

    /**
     * Create new user
     */
    public function create($data) {
        $defaults = [
            'FirstName' => '',
            'LastName' => '',
            'Email' => '',
            'Password' => '',
            'Role' => USER_ROLE_CUSTOMER,
            'PhoneNumber' => null,
            'Address' => null,
            'City' => null,
            'Country' => null,
            'CreatedAt' => date('Y-m-d H:i:s'),
            'UpdatedAt' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    /**
     * Update user profile
     */
    public function updateUser($id, $data) {
        $data['UpdatedAt'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    /**
     * Change password
     */
    public function changePassword($id, $newPassword) {
        return $this->update(
            ['Password' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])],
            "id = ?",
            [$id]
        );
    }

    /**
     * Update user role
     */
    public function updateRole($id, $role) {
        return $this->update(['Role' => $role], "id = ?", [$id]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        return $this->delete("id = ?", [$id]);
    }

    /**
     * Check if email exists (exclude specific user)
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Email = ?";
        $bindings = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $bindings[] = $excludeId;
        }

        return (int)$this->getValue($sql, $bindings) > 0;
    }

    /**
     * Count users by role
     */
    public function countByRole($role) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE Role = ?",
            [$role]
        );
    }

    /**
     * Get total user count
     */
    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }

    /**
     * Get users by role with pagination
     */
    public function getByRole($role, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE Role = ? ORDER BY CreatedAt DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->normalizeUsers($this->getRows($sql, [$role, $limit, $offset]));
        }
        
        return $this->normalizeUsers($this->getRows($sql, [$role]));
    }
}

?>
